const fs = require('fs');
const readline = require('readline');

async function reconstruct() {
    const transcriptPath = 'C:/Users/Lisbeth/.gemini/antigravity/brain/2acb3efe-2a61-4b12-a9a4-dca399e124c7/.system_generated/logs/transcript_full.jsonl';
    
    let files = {
        'c:\\\\xampp\\\\htdocs\\\\datanestiq\\\\prototype\\\\app.js': fs.existsSync('prototype/app.js') ? fs.readFileSync('prototype/app.js', 'utf8') : '',
        'c:\\\\xampp\\\\htdocs\\\\datanestiq\\\\prototype\\\\index.html': fs.existsSync('prototype/index.html') ? fs.readFileSync('prototype/index.html', 'utf8') : ''
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
                    if (tool.name === 'write_to_file') {
                        const target = tool.args.TargetFile.toLowerCase().replace(/\//g, '\\\\');
                        if (target.includes('prototype\\\\app.js') || target.includes('prototype\\\\index.html')) {
                            let key = Object.keys(files).find(k => k.toLowerCase() === target);
                            if(!key) { key = target; files[key] = ''; }
                            files[key] = tool.args.CodeContent;
                        }
                    } else if (tool.name === 'multi_replace_file_content' || tool.name === 'replace_file_content') {
                        const target = tool.args.TargetFile.toLowerCase().replace(/\//g, '\\\\');
                        if (target.includes('prototype\\\\app.js') || target.includes('prototype\\\\index.html')) {
                            let key = Object.keys(files).find(k => k.toLowerCase() === target);
                            if(key) {
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
            }
        } catch(e) {}
    }

    Object.keys(files).forEach(k => {
        if(k.includes('app.js')) fs.writeFileSync('prototype/app.js', files[k]);
        if(k.includes('index.html')) fs.writeFileSync('prototype/index.html', files[k]);
    });
    console.log('Reconstruction complete!');
}

reconstruct();
