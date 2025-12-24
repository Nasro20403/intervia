// =====================
// إعدادات WebSocket Server
// =====================
const WebSocket = require('ws');
const mysql = require('mysql2/promise');
const crypto = require('crypto');

// المنفذ
const WS_PORT = 8080;

// السرّ يجب أن يكون نفس الموجود داخل messages.php
const WS_SECRET = "fdhnjksldgnjg784uig98hygruhhjrerngh443hghrignreknjhberbg4y3bh3erpo32uwgwjgnhgo43ghhrwefjne2@kmgjeiu4ugj5$$$tjhnrink";

// إعدادات قاعدة البيانات (مطابقة تماماً لـ db.php)
const DB_CONF = {
    host: "localhost",
    user: "Nasro",
    password: "Nasro2010",
    database: "Nexo",
    waitForConnections: true,
    connectionLimit: 10
};

// =====================
// بدء تشغيل السيرفر
// =====================
const wss = new WebSocket.Server({ port: WS_PORT });
console.log("WebSocket server is running on port", WS_PORT);

// تخزين الاتصالات لكل مستخدم
const clients = new Map();

// اتصال DB Pool
const pool = mysql.createPool(DB_CONF);

// =====================
// التحقق من التوكن القادم من المتصفح
// =====================
function verifyToken(token) {
    try {
        const decoded = Buffer.from(token, "base64").toString("utf8");
        const parts = decoded.split(":");
        if (parts.length < 3) return null;

        const userId = parseInt(parts[0], 10);
        const expires = parseInt(parts[1], 10);
        const sigB64 = parts.slice(2).join(":");
        const signature = Buffer.from(sigB64, "base64");

        const payload = userId + ":" + expires;
        const expected = crypto
            .createHmac("sha256", WS_SECRET)
            .update(payload)
            .digest();

        if (!crypto.timingSafeEqual(expected, signature)) return null;
        if (expires < Math.floor(Date.now() / 1000)) return null;

        return userId;
    } catch {
        return null;
    }
}

// لإرسال بيانات
function send(ws, obj) {
    try {
        ws.send(JSON.stringify(obj));
    } catch {}
}

function broadcast(obj) {
    const msg = JSON.stringify(obj);
    wss.clients.forEach(client => {
        if (client.readyState === WebSocket.OPEN && client.userId) {
            try { client.send(msg); } catch {}
        }
    });
}


// =====================
// عند اتصال مستخدم بالسيرفر
// =====================
wss.on("connection", (ws) => {
    ws.isAlive = true;
    ws.userId = null;

    ws.on("pong", () => (ws.isAlive = true));

    ws.on("message", async (msg) => {
        let data;
        try {
            data = JSON.parse(msg);
        } catch {
            send(ws, { type: "error", message: "invalid_json" });
            return;
        }

            
                // إشعار مستخدم جديد (قادمة من PHP)
        if (data.type === "new_user") {
        
            // أمان بسيط: لا نقبل إلا من localhost
            const ip = ws._socket.remoteAddress;
            if (ip !== "127.0.0.1" && ip !== "::1") {
                send(ws, { type: "error", message: "forbidden" });
                return;
            }
        
            // بث المستخدم الجديد لكل المتصلين
            broadcast({
                type: "new_user",
                user: {
                    id: data.user.id,
                    fullname: data.user.fullname,
                    username: data.user.username,
                    profilePic: data.user.profilePic
                }
            });
        
            console.log("New user broadcasted:", data.user.username);
            return;
        }


        // أول خطوة: المستخدم يرسل init + token
        if (data.type === "init") {
            const uid = verifyToken(data.token);
            if (!uid) {
                send(ws, { type: "error", message: "invalid_token" });
                ws.close();
                return;
            }

            ws.userId = uid;

            if (!clients.has(uid)) clients.set(uid, new Set());
            clients.get(uid).add(ws);

            send(ws, { type: "auth_ok", user_id: uid });
            console.log("User connected:", uid);
            return;
        }

        // إذا المستخدم غير مصدّق
        if (!ws.userId) {
            send(ws, { type: "error", message: "not_authenticated" });
            return;
        }

        // إرسال رسالة
        if (data.type === "message") {
            const sender = ws.userId;
            const receiver = parseInt(data.receiver_id, 10);
            const text = (data.message || "").trim();
            const tempId = data.temp_id || null;

            if (!receiver || text === "") {
                send(ws, { type: "error", message: "invalid_message" });
                return;
            }

            try {
                // إضافة الرسالة إلى قاعدة البيانات
                const [result] = await pool.query(
                    "INSERT INTO messages (sender_id, receiver_id, message, created_at) VALUES (?,?,?,NOW())",
                    [sender, receiver, text]
                );

                const msgId = result.insertId;

                // رسالة جاهزة للإرسال للطرفين
                const obj = {
                    type: "message",
                    id: msgId,
                    sender_id: sender,
                    receiver_id: receiver,
                    message: text,
                    created_at: new Date().toISOString()
                };

                // تأكيد للمرسل
                send(ws, {
                    type: "sent_ack",
                    message_id: msgId,
                    temp_id: tempId
                });

                // إرسال للمستلم إذا كان متصل
                if (clients.has(receiver)) {
                    clients.get(receiver).forEach(cws => send(cws, obj));
                }

                // إرسال لكل جلسات المرسل الأخرى
                if (clients.has(sender)) {
                    clients.get(sender).forEach(cws => {
                        if (cws !== ws) send(cws, obj);
                    });
                }
            } catch (err) {
                console.log("DB error", err);
                send(ws, { type: "error", message: "db_error" });
            }
        }
    });

    ws.on("close", () => {
        if (ws.userId && clients.has(ws.userId)) {
            clients.get(ws.userId).delete(ws);
            if (clients.get(ws.userId).size === 0) {
                clients.delete(ws.userId);
            }
        }
    });
});

// =====================
// تنظيف الاتصالات الميتة
// =====================
setInterval(() => {
    wss.clients.forEach((ws) => {
        if (!ws.isAlive) return ws.terminate();
        ws.isAlive = false;
        ws.ping();
    });
}, 30000);
