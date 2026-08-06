import { pipeline, env } from 'https://cdn.jsdelivr.net/npm/@xenova/transformers@2.17.2/dist/transformers.min.js';

// Skip local check, fetch from CDN/IndexedDB cache
env.allowLocalModels = false;
env.useBrowserCache = true; // Use IndexedDB

class PipelineSingleton {
    static task = 'feature-extraction';
    static model = 'Xenova/paraphrase-multilingual-MiniLM-L12-v2';
    static instance = null;

    static async getInstance(progress_callback = null) {
        if (this.instance === null) {
            this.instance = pipeline(this.task, this.model, { 
                progress_callback,
                quantized: true,
                dtype: 'q8'
            });
        }
        return this.instance;
    }
}

// Module-level cache for corpus embeddings
let cachedCorpusData = null; // { embeddingsData: Float32Array, textCount: number, dim: number, hash: string }

function computeSignature(texts) {
    if (!texts || texts.length === 0) return '';
    return texts.length + ':' + texts[0].slice(0, 30) + ':' + texts[texts.length - 1].slice(0, 30);
}

async function indexCorpus(extractor, corpusTexts) {
    if (!corpusTexts || corpusTexts.length === 0) return null;
    const signature = computeSignature(corpusTexts);
    if (cachedCorpusData && cachedCorpusData.hash === signature && cachedCorpusData.textCount === corpusTexts.length) {
        return cachedCorpusData;
    }

    const corpusOutput = await extractor(corpusTexts, { pooling: 'mean', normalize: true });
    const dim = corpusOutput.dims ? corpusOutput.dims[1] : (corpusOutput.data.length / corpusTexts.length);
    
    cachedCorpusData = {
        embeddingsData: corpusOutput.data,
        textCount: corpusTexts.length,
        dim: dim,
        hash: signature
    };

    return cachedCorpusData;
}

self.addEventListener('message', async (event) => {
    const data = event.data;

    if (data.type === 'warmup') {
        try {
            await PipelineSingleton.getInstance(x => {
                self.postMessage({ status: x.status, name: x.name, file: x.file, progress: x.progress });
            });
            self.postMessage({ status: 'ready' });
        } catch (e) {
            self.postMessage({ status: 'error', error: e.message });
        }
        return;
    }

    if (data.type === 'index') {
        try {
            const extractor = await PipelineSingleton.getInstance(x => {
                self.postMessage({ status: x.status, name: x.name, file: x.file, progress: x.progress });
            });
            if (data.corpusTexts) {
                await indexCorpus(extractor, data.corpusTexts);
            }
            self.postMessage({ status: 'indexed', id: data.id });
        } catch (e) {
            self.postMessage({ status: 'error', id: data.id, error: e.message });
        }
        return;
    }

    // Default or { type: 'search' }
    const { query, corpusTexts, id } = data;
    if (!query) return;

    try {
        const extractor = await PipelineSingleton.getInstance(x => {
            self.postMessage({ status: x.status, name: x.name, file: x.file, progress: x.progress });
        });

        // Ensure corpus is indexed (Cache-miss fallback per Precisión 1)
        let corpusData = cachedCorpusData;
        if ((!corpusData || (corpusTexts && computeSignature(corpusTexts) !== corpusData.hash)) && corpusTexts && corpusTexts.length > 0) {
            corpusData = await indexCorpus(extractor, corpusTexts);
        }

        if (!corpusData) {
            self.postMessage({ status: 'error', error: 'Corpus not indexed yet and no corpusTexts provided.' });
            return;
        }

        // 1. Embed ONLY the query (fast: ~15-40ms)
        const queryOutput = await extractor(query, { pooling: 'mean', normalize: true });
        const queryData = queryOutput.data;
        const dim = corpusData.dim;
        const count = corpusData.textCount;
        const corpusDataArray = corpusData.embeddingsData;

        // 2. Compute dot product (since vectors are normalized, dot product = cosine similarity)
        const similarities = new Array(count);
        for (let i = 0; i < count; ++i) {
            let sum = 0;
            const offset = i * dim;
            for (let j = 0; j < dim; ++j) {
                sum += queryData[j] * corpusDataArray[offset + j];
            }
            similarities[i] = { index: i, score: sum };
        }

        similarities.sort((a, b) => b.score - a.score);
        self.postMessage({ status: 'complete', id, results: similarities });
    } catch (e) {
        self.postMessage({ status: 'error', error: e.message });
    }
});
