const BASE = import.meta.env.PUBLIC_API_BASE ?? '';

export const CHAT_API = `${BASE}/api/chat.php`;
export const SAVE_WIZARD_API = `${BASE}/api/save_wizard.php`;
export const WORKER_URL = `${BASE}/worker.js`;
