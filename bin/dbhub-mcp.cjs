const fs = require('fs');
const path = require('path');
const { spawn } = require('child_process');

const rootDir = path.resolve(__dirname, '..');
let dsn = '';

for (const envFile of ['.env.local', '.env']) {
  const envPath = path.join(rootDir, envFile);
  if (fs.existsSync(envPath)) {
    const content = fs.readFileSync(envPath, 'utf8');
    const dsnMatch = content.match(/^DSN=["']?([^"'\r\n]+)["']?/m);
    const dbUrlMatch = content.match(/^DATABASE_URL=["']?([^"'\r\n]+)["']?/m);
    if (dsnMatch) {
      dsn = dsnMatch[1];
      break;
    } else if (dbUrlMatch) {
      dsn = dbUrlMatch[1];
      break;
    }
  }
}

if (!dsn) {
  console.error('[dbhub-mcp] Không tìm thấy DSN hoặc DATABASE_URL trong file .env');
  process.exit(1);
}

const child = spawn('npx', ['-y', '@bytebase/dbhub@1.3.1'], {
  stdio: 'inherit',
  env: {
    ...process.env,
    DSN: dsn,
  },
});

child.on('exit', (code) => {
  process.exit(code ?? 0);
});
