const fs = require('fs');
const readline = require('readline');

async function reconstruct() {
    const transcriptPath = 'C:/Users/Lisbeth/.gemini/antigravity/brain/2acb3efe-2a61-4b12-a9a4-dca399e124c7/.system_generated/logs/transcript_full.jsonl';
    
    let files = {
        'app.js': '',
        'index.html': ''
    };

    const rl = readline.createInterface({
        input: fs.createReadStream(transcriptPath),
        crlfDelay: Infinity
    });

    for await (const line of rl) {
        try {
            const step = JSON.parse(line);
            if (step.tool_calls) {
                for (const tool of step.tool_calls) {
                    if (tool.name === 'write_to_file' || tool.name === 'write_file') {
                        const target = tool.args.TargetFile;
                        if (target && target.endsWith('app.js')) {
                            files['app.js'] = tool.args.CodeContent;
                        } else if (target && target.endsWith('index.html')) {
                            files['index.html'] = tool.args.CodeContent;
                        }
                    } else if (tool.name === 'multi_replace_file_content' || tool.name === 'replace_file_content') {
                        const target = tool.args.TargetFile;
                        let key = null;
                        if (target && target.endsWith('app.js')) key = 'app.js';
                        else if (target && target.endsWith('index.html')) key = 'index.html';
                        
                        if(key && files[key]) {
                            let content = files[key];
                            const chunks = tool.args.ReplacementChunks || [tool.args];
                            for(const chunk of chunks) {
                                content = content.replace(chunk.TargetContent, chunk.ReplacementContent);
                            }
                            files[key] = content;
                        }
                    }
                }
            }
        } catch(e) {}
    }

    fs.writeFileSync('prototype/app.js', files['app.js']);
    fs.writeFileSync('prototype/index.html', files['index.html']);
    console.log('Reconstruction complete! app.js length:', files['app.js'].length);
}

reconstruct();
