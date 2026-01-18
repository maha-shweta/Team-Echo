<?php
/**
 * Team Echo - FAQ Specialist (Max 10)
 * Features: 10 Bilingual FAQs, NLP Sentiment, No Stats.
 */
$chatbot_user_role = $_SESSION['role'] ?? 'guest';
$chatbot_user_name = $_SESSION['name'] ?? 'Guest';
?>

<style>
    /* Styling remains consistent with previous version */
    .chatbot-container { position: fixed; bottom: 20px; right: 20px; z-index: 1000; font-family: 'Inter', sans-serif; }
    .chatbot-button { width: 60px; height: 60px; border-radius: 50%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; cursor: pointer; box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4); display: flex; align-items: center; justify-content: center; }
    .chatbot-button svg { width: 28px; height: 28px; fill: white; }
    .chatbot-window { position: fixed; bottom: 95px; right: 20px; width: 370px; height: 550px; background: #fff; border-radius: 16px; box-shadow: 0 12px 48px rgba(0,0,0,0.15); display: none; flex-direction: column; overflow: hidden; border: 1px solid rgba(0,0,0,0.05); }
    .chatbot-window.active { display: flex; animation: cb-pop 0.3s ease; }
    .chatbot-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px; display: flex; justify-content: space-between; align-items: center; }
    .chatbot-messages { flex: 1; overflow-y: auto; padding: 15px; background: #f8f9fa; scroll-behavior: smooth; }
    .message { margin-bottom: 12px; display: flex; gap: 8px; }
    .message.user { justify-content: flex-end; }
    .message-bubble { max-width: 85%; padding: 10px 14px; border-radius: 15px; font-size: 13.5px; line-height: 1.4; }
    .message.bot .message-bubble { background: white; border: 1px solid #edf2f7; }
    .message.user .message-bubble { background: #667eea; color: white; }
    .sentiment-badge { font-size: 10px; padding: 2px 8px; border-radius: 10px; margin-top: 5px; display: inline-block; font-weight: bold; }
    .sent-pos { background: #e6fffa; color: #2c7a7b; }
    .sent-neg { background: #fff5f5; color: #c53030; }
    .sent-neu { background: #edf2f7; color: #4a5568; }
    .quick-replies { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 10px; }
    .qr-btn { background: #fff; border: 1px solid #667eea; color: #667eea; padding: 5px 12px; border-radius: 15px; cursor: pointer; font-size: 11.5px; transition: 0.2s; }
    .qr-btn:hover { background: #667eea; color: white; }
    .chatbot-input-area { padding: 12px; border-top: 1px solid #edf2f7; display: flex; gap: 8px; }
    .chatbot-input { flex: 1; border: 1px solid #edf2f7; border-radius: 20px; padding: 8px 15px; outline: none; }
</style>

<div class="chatbot-container">
    <button class="chatbot-button" onclick="toggleChatbot()">
        <svg viewBox="0 0 24 24"><path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2z"/></svg>
    </button>
    <div class="chatbot-window" id="chatbotWindow">
        <div class="chatbot-header">
            <div><strong>Echo FAQ Bot</strong><br><small id="status">Online</small></div>
            <select onchange="changeLang(this.value)" style="border:none; border-radius:4px; font-size:11px;">
                <option value="en">EN</option>
                <option value="bn">বাংলা</option>
            </select>
        </div>
        <div class="chatbot-messages" id="chatbotMessages"></div>
        <div class="chatbot-input-area">
            <input type="text" id="chatbotInput" class="chatbot-input" placeholder="Ask a question..." onkeypress="if(event.key==='Enter') sendMessage()">
        </div>
    </div>
</div>

<script>
const botConfig = { role: '<?php echo $chatbot_user_role; ?>', name: '<?php echo $chatbot_user_name; ?>', lang: 'en' };

const lex = {
    en: { pos: ['good', 'thanks', 'happy', 'great'], neg: ['bad', 'error', 'slow', 'wrong'] },
    bn: { pos: ['ভালো', 'ধন্যবাদ', 'ঠিক'], neg: ['ভুল', 'খারাপ', 'সমস্যা'] }
};

const translations = {
    en: {
        welcome: "How can I help you with Team Echo today?",
        tone: "Tone",
        reply: "Here is the information you requested:",
        faqs: [
            { q: "🔒 Privacy", a: "Anonymous mode hides your name in all reports." },
            { q: "🛠️ Resolution", a: "Admins mark issues as resolved once fixed." },
            { q: "🚩 Priorities", a: "Levels: Low, Medium, High, and Critical." },
            { q: "📊 Categories", a: "Feedback is grouped by departments like IT or HR." },
            { q: "🔑 My Role", a: "Your access depends on your assigned system role." },
            { q: "📧 Contact", a: "Reach out to the admin for account issues." },
            { q: "📱 Mobile", a: "Yes, the dashboard is fully responsive." },
            { q: "⏱️ Response Time", a: "Critical issues are usually checked in 24h." },
            { q: "📝 Editing", a: "You can edit your feedback before submission." },
            { q: "💾 Data Export", a: "Admins can export reports in Excel format." }
        ]
    },
    bn: {
        welcome: "টিম ইকো নিয়ে আমি আপনাকে কীভাবে সাহায্য করতে পারি?",
        tone: "টোন",
        reply: "আপনার প্রয়োজনীয় তথ্য এখানে দেওয়া হলো:",
        faqs: [
            { q: "🔒 গোপনীয়তা", a: "বেনামী মোড আপনার নাম রিপোর্ট থেকে সরিয়ে রাখে।" },
            { q: "🛠️ সমাধান", a: "অ্যাডমিন সমস্যা ঠিক করার পর রেজলভড মার্ক করেন।" },
            { q: "🚩 অগ্রাধিকার", a: "স্তর: সাধারণ, মাঝারি, উচ্চ এবং জরুরি।" },
            { q: "📊 বিভাগ", a: "ফিডব্যাকগুলো আইটি বা এইচআর বিভাগে ভাগ করা হয়।" },
            { q: "🔑 আমার রোল", a: "আপনার অ্যাক্সেস সিস্টেম রোলের ওপর নির্ভর করে।" },
            { q: "📧 যোগাযোগ", a: "অ্যাকাউন্ট সমস্যার জন্য অ্যাডমিনের সাথে যোগাযোগ করুন।" },
            { q: "📱 মোবাইল", a: "হ্যাঁ, ড্যাশবোর্ডটি মোবাইলে ব্যবহারযোগ্য।" },
            { q: "⏱️ সময়", a: "জরুরি বিষয়গুলো সাধারণত ২৪ ঘণ্টায় দেখা হয়।" },
            { q: "📝 এডিটিং", a: "সাবমিট করার আগে আপনি ফিডব্যাক এডিট করতে পারেন।" },
            { q: "💾 এক্সপোর্ট", a: "অ্যাডমিনরা এক্সেল ফরম্যাটে রিপোর্ট ডাউনলোড করতে পারেন।" }
        ]
    }
};

function toggleChatbot() {
    const win = document.getElementById('chatbotWindow');
    win.classList.toggle('active');
    if(win.classList.contains('active') && document.getElementById('chatbotMessages').innerHTML === "") bootBot();
}

function bootBot() { addMsg('bot', translations[botConfig.lang].welcome); showFaqMenu(); }

function changeLang(l) { botConfig.lang = l; document.getElementById('chatbotMessages').innerHTML = ""; bootBot(); }

function showFaqMenu() {
    const container = document.getElementById('chatbotMessages');
    const div = document.createElement('div');
    div.className = 'quick-replies';
    translations[botConfig.lang].faqs.forEach((item, index) => {
        div.innerHTML += `<button class="qr-btn" onclick="handleFaq(${index})">${item.q}</button>`;
    });
    container.appendChild(div);
    scroll();
}

function handleFaq(index) {
    document.querySelectorAll('.quick-replies').forEach(m => m.remove());
    addMsg('user', translations[botConfig.lang].faqs[index].q);
    setTimeout(() => {
        addMsg('bot', translations[botConfig.lang].faqs[index].a);
        setTimeout(showFaqMenu, 1000);
    }, 400);
}

function sendMessage() {
    const input = document.getElementById('chatbotInput');
    const val = input.value.trim();
    if(!val) return;
    addMsg('user', val);
    input.value = "";
    
    let score = 0;
    val.toLowerCase().split(/\W+/).forEach(w => {
        if(lex[botConfig.lang].pos.includes(w)) score++;
        if(lex[botConfig.lang].neg.includes(w)) score--;
    });

    const mood = score > 0 ? {l:'Positive', c:'sent-pos'} : score < 0 ? {l:'Negative', c:'sent-neg'} : {l:'Neutral', c:'sent-neu'};

    setTimeout(() => {
        addMsg('bot', `${translations[botConfig.lang].reply}<br><span class="sentiment-badge ${mood.c}">${translations[botConfig.lang].tone}: ${mood.l}</span>`);
        setTimeout(showFaqMenu, 1000);
    }, 600);
}

function addMsg(type, content) {
    const container = document.getElementById('chatbotMessages');
    const msgDiv = document.createElement('div');
    msgDiv.className = `message ${type}`;
    msgDiv.innerHTML = `<div class="message-bubble">${content}</div>`;
    container.appendChild(msgDiv);
    scroll();
}

function scroll() { const m = document.getElementById('chatbotMessages'); m.scrollTop = m.scrollHeight; }
</script>