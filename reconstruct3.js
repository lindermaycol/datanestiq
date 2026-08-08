const fs = require('fs');
const path = require('path');
const readline = require('readline');

async function processTranscript(transcriptPath, files) {
    if (!fs.existsSync(transcriptPath)) return;
    const rl = readline.createInterface({
        input: fs.createReadStream(transcriptPath),
        crlfDelay: Infinity
    });

    for await (const line of rl) {
        try {
            const step = JSON.parse(line);
            if (step.tool_calls) {
                for (const tool of step.tool_calls) {
                    const args = typeof tool.args === 'string' ? JSON.parse(tool.args) : tool.args;
                    if (!args || !args.TargetFile) continue;
                    
                    const target = args.TargetFile.toLowerCase().replace(/\\\\/g, '/');
                    let key = null;
                    if (target.endsWith('prototype/app.js')) key = 'app.js';
                    if (target.endsWith('prototype/index.html')) key = 'index.html';
                    if (target.endsWith('prototype/styles.css')) key = 'styles.css';
                    
                    if (!key) continue;

                    if (tool.name === 'write_to_file' || tool.name === 'write_file') {
                        files[key] = args.CodeContent || '';
                    } else if (tool.name === 'replace_file_content' || tool.name === 'multi_replace_file_content') {
                        if (files[key] === undefined) files[key] = '';
                        let content = files[key];
                        const chunks = args.ReplacementChunks || [args];
                        for(const chunk of chunks) {
                            if (chunk.TargetContent) {
                                content = content.split(chunk.TargetContent).join(chunk.ReplacementContent || '');
                            }
                        }
                        files[key] = content;
                    }
                }
            }
        } catch(e) {}
    }
}

async function run() {
    let files = {};
    const conversations = [
        '3a1df107-1fee-4fe5-8b61-2a4582e02d84',
        '2acb3efe-2a61-4b12-a9a4-dca399e124c7',
        '1bef5888-395f-4f39-8c4f-53d6cb48378e' // Astro migration, might have deleted it but we'll stop before it
    ];

    for (let c of conversations.slice(0, 2)) {
        const tPath = 'C:/Users/Lisbeth/.gemini/antigravity/brain/' + c + '/.system_generated/logs/transcript_full.jsonl';
        await processTranscript(tPath, files);
    }
    
    if(!fs.existsSync('prototype')) fs.mkdirSync('prototype');
    for(let k in files) {
        fs.writeFileSync('prototype/' + k, files[k]);
        console.log('Restored', k, files[k].length, 'bytes');
    }
}

run();
