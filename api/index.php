
<?php
// Backend Payload Generator for Real-Time Testing (Zero external dependencies)
if (isset($_GET['action'])) {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Access-Control-Allow-Origin: *');

    if ($_GET['action'] === 'ping') {
        header('Content-Type: application/json');
        echo json_encode(['status' => 'pong', 'ts' => microtime(true)]);
        exit;
    }

    if ($_GET['action'] === 'download') {
        header('Content-Type: application/octet-stream');
        $chunkSize = 1024 * 64; // 64KB
        $data = str_repeat('0123456789ABCDEF', $chunkSize / 16);
        for ($i = 0; $i < 32; $i++) { // 32 * 64KB = 2MB
            echo $data;
            flush();
        }
        exit;
    }

    if ($_GET['action'] === 'upload') {
        $input = file_get_contents('php://input');
        header('Content-Type: application/json');
        echo json_encode(['received' => strlen($input)]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Internet Speed Test | PintO</title>
    <style>
        :root {
            --bg: #07090e;
            --card: rgba(16, 22, 34, 0.75);
            --border: rgba(0, 243, 255, 0.2);
            --cyan: #00f3ff;
            --green: #00ff88;
            --pink: #ff0055;
            --yellow: #ffbe0b;
            --text: #e2e8f0;
            --muted: #64748b;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Courier New', Courier, monospace; }
        body {
            background: radial-gradient(circle at 50% 0%, #0f1b2e 0%, var(--bg) 70%);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px 15px;
        }
        .header {
            width: 100%;
            max-width: 750px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border);
            padding-bottom: 14px;
            margin-bottom: 20px;
            gap: 10px;
        }
        .brand-wrap {
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .brand {
            font-size: 1.15rem;
            font-weight: bold;
            color: var(--cyan);
            letter-spacing: 1.5px;
            text-transform: uppercase;
            text-shadow: 0 0 10px rgba(0, 243, 255, 0.4);
        }

        /* Spinning PintO Cyber Badge */
        .pinto-orbit {
            position: relative;
            width: 58px;
            height: 58px;
            display: flex;
            align-items: center;
            justify-content: center;
            perspective: 400px;
        }
        .pinto-ring {
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            border: 2px dashed var(--pink);
            border-top-color: var(--cyan);
            border-bottom-color: var(--green);
            animation: spinRing 4s linear infinite;
            box-shadow: 0 0 12px rgba(255, 0, 85, 0.35);
        }
        .pinto-text {
            font-size: 0.85rem;
            font-weight: 900;
            letter-spacing: 1px;
            color: #fff;
            text-shadow: 0 0 8px var(--cyan), 0 0 15px var(--pink);
            animation: spin3DText 3.5s ease-in-out infinite;
            display: inline-block;
        }

        @keyframes spinRing {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        @keyframes spin3DText {
            0% { transform: rotateY(0deg) scale(1); color: var(--cyan); }
            50% { transform: rotateY(180deg) scale(1.12); color: var(--pink); }
            100% { transform: rotateY(360deg) scale(1); color: var(--cyan); }
        }

        .badge {
            font-size: 0.72rem;
            padding: 5px 10px;
            border: 1px solid var(--cyan);
            border-radius: 4px;
            background: rgba(0, 243, 255, 0.1);
            color: var(--cyan);
            white-space: nowrap;
        }
        .dashboard {
            width: 100%;
            max-width: 750px;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        .card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 16px;
            backdrop-filter: blur(10px);
            position: relative;
            overflow: hidden;
        }
        .card.full { grid-column: span 2; }
        .label { font-size: 0.75rem; color: var(--muted); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 6px; }
        .value { font-size: 2rem; font-weight: bold; color: #fff; }
        .unit { font-size: 0.85rem; color: var(--cyan); margin-left: 4px; }
        .sub-stat { font-size: 0.75rem; color: var(--muted); margin-top: 6px; display: flex; justify-content: space-between; }
        
        /* Oscilloscope Canvas */
        canvas {
            width: 100%;
            height: 140px;
            background: rgba(0, 0, 0, 0.4);
            border-radius: 6px;
            border: 1px solid rgba(255, 255, 255, 0.05);
            margin-top: 8px;
        }

        /* Bufferbloat & Stability Meter */
        .bar-wrap { width: 100%; height: 8px; background: #1e293b; border-radius: 4px; margin-top: 10px; overflow: hidden; }
        .bar-fill { height: 100%; width: 0%; background: var(--green); transition: width 0.3s, background 0.3s; }

        /* Action Button */
        .btn-start {
            grid-column: span 2;
            padding: 16px;
            font-size: 1.1rem;
            font-weight: bold;
            letter-spacing: 2px;
            color: var(--bg);
            background: var(--cyan);
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-transform: uppercase;
            box-shadow: 0 0 20px rgba(0, 243, 255, 0.4);
            transition: 0.2s;
        }
        .btn-start:hover { background: #fff; box-shadow: 0 0 25px rgba(255, 255, 255, 0.6); }
        .btn-start:disabled { background: #334155; color: #94a3b8; box-shadow: none; cursor: not-allowed; }

        /* Workload Matrix */
        .matrix { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-top: 10px; }
        .m-item {
            background: rgba(0,0,0,0.3);
            padding: 10px;
            border-radius: 6px;
            text-align: center;
            border: 1px solid rgba(255,255,255,0.05);
        }
        .m-title { font-size: 0.7rem; color: var(--muted); }
        .m-status { font-size: 0.85rem; font-weight: bold; margin-top: 4px; color: var(--muted); }

        @media (max-width: 550px) {
            .dashboard { grid-template-columns: 1fr; }
            .card.full, .btn-start { grid-column: span 1; }
            .value { font-size: 1.6rem; }
            .brand { font-size: 0.95rem; }
            .pinto-orbit { width: 48px; height: 48px; }
            .pinto-text { font-size: 0.72rem; }
        }
    </style>
</head>
<body>

    <div class="header">
        <div class="brand-wrap">
            <div class="pinto-orbit">
                <div class="pinto-ring"></div>
                <span class="pinto-text">PintO</span>
            </div>
            <div class="brand">INTERNET SPEED TEST</div>
        </div>
        <div class="badge" id="statusBadge">IDLE // READY</div>
    </div>

    <div class="dashboard">
        <!-- Download Card -->
        <div class="card">
            <div class="label">Multi-Thread Throughput (DL)</div>
            <div><span class="value" id="dlSpeed">0.00</span><span class="unit">Mbps</span></div>
            <div class="sub-stat">
                <span>Real-time: <b id="dlMB" style="color:var(--green)">0.0 MB/s</b></span>
                <span>Peak: <b id="dlPeak">0.0</b></span>
            </div>
        </div>

        <!-- Upload Card -->
        <div class="card">
            <div class="label">Upstream Burst (UL)</div>
            <div><span class="value" id="ulSpeed">0.00</span><span class="unit">Mbps</span></div>
            <div class="sub-stat">
                <span>Real-time: <b id="ulMB" style="color:var(--cyan)">0.0 MB/s</b></span>
                <span>Peak: <b id="ulPeak">0.0</b></span>
            </div>
        </div>

        <!-- Ping & Bufferbloat Card -->
        <div class="card">
            <div class="label">Idle vs Loaded Ping (Bufferbloat)</div>
            <div><span class="value" id="pingVal">--</span><span class="unit">ms</span></div>
            <div class="sub-stat">
                <span>Jitter: <b id="jitterVal">-- ms</b></span>
                <span>Load Spike: <b id="bloatVal" style="color:var(--yellow)">+0 ms</b></span>
            </div>
            <div class="bar-wrap"><div class="bar-fill" id="bloatBar"></div></div>
        </div>

        <!-- Stability Score Card -->
        <div class="card">
            <div class="label">Line Stability & Grade</div>
            <div><span class="value" id="gradeVal">--</span><span class="unit" id="gradeSub">WAITING</span></div>
            <div class="sub-stat">
                <span>Consistency: <b id="consistVal">--%</b></span>
                <span>Threads: <b>6x Parallel</b></span>
            </div>
            <div class="bar-wrap"><div class="bar-fill" id="stabBar"></div></div>
        </div>

        <!-- Live Oscilloscope -->
        <div class="card full">
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <div class="label">Live Speed & Latency Oscilloscope</div>
                <div style="font-size:0.7rem;">
                    <span style="color:var(--cyan)">■ Speed</span> &nbsp;
                    <span style="color:var(--pink)">■ Ping Spike</span>
                </div>
            </div>
            <canvas id="scopeCanvas" width="700" height="140"></canvas>
        </div>

        <!-- Real-World Workload Verdict -->
        <div class="card full">
            <div class="label">Real-World Capability Diagnostics</div>
            <div class="matrix">
                <div class="m-item">
                    <div class="m-title">4K HDR STREAM</div>
                    <div class="m-status" id="wStream">--</div>
                </div>
                <div class="m-item">
                    <div class="m-title">COMPETITIVE GAMING</div>
                    <div class="m-status" id="wGame">--</div>
                </div>
                <div class="m-item">
                    <div class="m-title">HEAVY LEECH / TORRENT</div>
                    <div class="m-status" id="wLeech">--</div>
                </div>
            </div>
        </div>

        <button class="btn-start" id="startBtn" onclick="runDiagnostics()">INITIATE STRESS TEST</button>
    </div>

    <script>
        const canvas = document.getElementById('scopeCanvas');
        const ctx = canvas.getContext('2d');
        let speedHistory = new Array(60).fill(0);
        let pingHistory = new Array(60).fill(0);
        let isRunning = false;

        function drawGraph() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            
            ctx.strokeStyle = 'rgba(255, 255, 255, 0.05)';
            ctx.lineWidth = 1;
            for (let i = 1; i < 4; i++) {
                let y = (canvas.height / 4) * i;
                ctx.beginPath(); ctx.moveTo(0, y); ctx.lineTo(canvas.width, y); ctx.stroke();
            }

            const maxSpeed = Math.max(...speedHistory, 50);
            const maxPing = Math.max(...pingHistory, 100);

            ctx.strokeStyle = '#00f3ff';
            ctx.lineWidth = 2.5;
            ctx.beginPath();
            speedHistory.forEach((val, idx) => {
                let x = (idx / (speedHistory.length - 1)) * canvas.width;
                let y = canvas.height - (val / maxSpeed) * (canvas.height - 20) - 10;
                idx === 0 ? ctx.moveTo(x, y) : ctx.lineTo(x, y);
            });
            ctx.stroke();

            ctx.strokeStyle = '#ff0055';
            ctx.lineWidth = 1.5;
            ctx.beginPath();
            pingHistory.forEach((val, idx) => {
                let x = (idx / (pingHistory.length - 1)) * canvas.width;
                let y = canvas.height - (val / maxPing) * (canvas.height - 20) - 10;
                idx === 0 ? ctx.moveTo(x, y) : ctx.lineTo(x, y);
            });
            ctx.stroke();
        }

        async function measurePing() {
            const t0 = performance.now();
            await fetch('?action=ping&r=' + Math.random(), { cache: 'no-store' });
            return performance.now() - t0;
        }

        async function runDiagnostics() {
            if (isRunning) return;
            isRunning = true;
            const btn = document.getElementById('startBtn');
            const badge = document.getElementById('statusBadge');
            btn.disabled = true;
            speedHistory.fill(0);
            pingHistory.fill(0);

            badge.innerText = 'PHASE 1 // MEASURING JITTER';
            badge.style.color = '#ffbe0b';
            let pings = [];
            for (let i = 0; i < 8; i++) {
                let p = await measurePing();
                pings.push(p);
                pingHistory.push(p); pingHistory.shift();
                drawGraph();
                document.getElementById('pingVal').innerText = Math.round(p);
                await new Promise(r => setTimeout(r, 120));
            }
            const idlePing = pings.reduce((a, b) => a + b, 0) / pings.length;
            let jitterSum = 0;
            for (let i = 1; i < pings.length; i++) jitterSum += Math.abs(pings[i] - pings[i-1]);
            const jitter = jitterSum / (pings.length - 1);
            
            document.getElementById('pingVal').innerText = Math.round(idlePing);
            document.getElementById('jitterVal').innerText = jitter.toFixed(1) + ' ms';

            badge.innerText = 'PHASE 2 // STRESS DOWNLOAD & BLOAT';
            badge.style.color = '#00f3ff';
            let totalBytes = 0;
            let dlSamples = [];
            let loadedPings = [];
            let dlActive = true;
            const startTime = performance.now();
            const duration = 7000;

            const pingMonitor = setInterval(async () => {
                if (!dlActive) return;
                let lp = await measurePing();
                loadedPings.push(lp);
                pingHistory.push(lp); pingHistory.shift();
                let bloat = Math.max(0, lp - idlePing);
                document.getElementById('bloatVal').innerText = '+' + Math.round(bloat) + ' ms';
                let bloatPct = Math.min(100, (bloat / 150) * 100);
                const bloatBar = document.getElementById('bloatBar');
                bloatBar.style.width = bloatPct + '%';
                bloatBar.style.background = bloat > 80 ? '#ff0055' : (bloat > 30 ? '#ffbe0b' : '#00ff88');
            }, 600);

            const uiTimer = setInterval(() => {
                let elapsed = (performance.now() - startTime) / 1000;
                if (elapsed > 0) {
                    let mbps = (totalBytes * 8) / (elapsed * 1000000);
                    let mbs = totalBytes / (elapsed * 1048576);
                    dlSamples.push(mbps);
                    speedHistory.push(mbps); speedHistory.shift();
                    drawGraph();

                    document.getElementById('dlSpeed').innerText = mbps.toFixed(2);
                    document.getElementById('dlMB').innerText = mbs.toFixed(2) + ' MB/s';
                    document.getElementById('dlPeak').innerText = Math.max(...dlSamples).toFixed(1) + ' Mbps';
                }
            }, 150);

            const worker = async () => {
                while (performance.now() - startTime < duration) {
                    const res = await fetch('?action=download&r=' + Math.random());
                    const reader = res.body.getReader();
                    while (true) {
                        const { done, value } = await reader.read();
                        if (done || performance.now() - startTime >= duration) break;
                        totalBytes += value.length;
                    }
                }
            };

            await Promise.all([worker(), worker(), worker(), worker(), worker(), worker()]);
            dlActive = false;
            clearInterval(pingMonitor);
            clearInterval(uiTimer);

            const finalDL = parseFloat(document.getElementById('dlSpeed').innerText);

            badge.innerText = 'PHASE 3 // UPSTREAM BURST';
            badge.style.color = '#00ff88';
            let ulBytes = 0;
            let ulSamples = [];
            const ulStart = performance.now();
            const ulDuration = 5000;
            const payload = new Uint8Array(512 * 1024);

            const ulWorker = async () => {
                while (performance.now() - ulStart < ulDuration) {
                    await fetch('?action=upload', { method: 'POST', body: payload });
                    ulBytes += payload.length;
                    let elapsed = (performance.now() - ulStart) / 1000;
                    let mbps = (ulBytes * 8) / (elapsed * 1000000);
                    let mbs = ulBytes / (elapsed * 1048576);
                    ulSamples.push(mbps);
                    speedHistory.push(mbps); speedHistory.shift();
                    drawGraph();

                    document.getElementById('ulSpeed').innerText = mbps.toFixed(2);
                    document.getElementById('ulMB').innerText = mbs.toFixed(2) + ' MB/s';
                    document.getElementById('ulPeak').innerText = Math.max(...ulSamples).toFixed(1) + ' Mbps';
                }
            };

            await Promise.all([ulWorker(), ulWorker(), ulWorker()]);

            badge.innerText = 'DIAGNOSTICS COMPLETE';
            badge.style.color = '#00ff88';

            const avgSample = dlSamples.reduce((a, b) => a + b, 0) / (dlSamples.length || 1);
            const variance = dlSamples.reduce((a, b) => a + Math.abs(b - avgSample), 0) / (dlSamples.length || 1);
            const consistency = Math.max(0, Math.min(100, Math.round(100 - (variance / (avgSample || 1)) * 100)));
            
            document.getElementById('consistVal').innerText = consistency + '%';
            const stabBar = document.getElementById('stabBar');
            stabBar.style.width = consistency + '%';
            stabBar.style.background = consistency > 75 ? '#00ff88' : (consistency > 50 ? '#ffbe0b' : '#ff0055');

            let grade = 'S-TIER';
            let sub = 'FIBER / 5G ULTRA';
            if (finalDL < 10) { grade = 'C-TIER'; sub = 'BASIC BROWSING'; }
            else if (finalDL < 40) { grade = 'B-TIER'; sub = 'STABLE HD'; }
            else if (finalDL < 100) { grade = 'A-TIER'; sub = 'HIGH SPEED'; }
            document.getElementById('gradeVal').innerText = grade;
            document.getElementById('gradeSub').innerText = sub;

            const avgBloat = loadedPings.length ? (loadedPings.reduce((a,b)=>a+b,0)/loadedPings.length) - idlePing : 0;
            
            const wStream = document.getElementById('wStream');
            wStream.innerText = finalDL >= 25 ? 'OPTIMAL (NO BUFFER)' : (finalDL >= 8 ? '1080p ONLY' : 'POOR');
            wStream.style.color = finalDL >= 25 ? '#00ff88' : '#ffbe0b';

            const wGame = document.getElementById('wGame');
            wGame.innerText = (idlePing < 60 && avgBloat < 50) ? 'ZERO LAG' : 'SPIKE RISK';
            wGame.style.color = (idlePing < 60 && avgBloat < 50) ? '#00ff88' : '#ff0055';

            const wLeech = document.getElementById('wLeech');
            wLeech.innerText = (finalDL >= 50 && consistency >= 70) ? 'TURBO READY' : 'MODERATE';
            wLeech.style.color = (finalDL >= 50 && consistency >= 70) ? '#00ff88' : '#00f3ff';

            btn.disabled = false;
            btn.innerText = 'RE-RUN STRESS TEST';
            isRunning = false;
        }

        drawGraph();
    </script>
</body>
</html>
