from flask import Flask, request, jsonify
from textblob import TextBlob  # simple sentiment analysis
# You can also use transformers / sklearn / custom models

app = Flask(__name__)

@app.route('/analyze', methods=['POST'])
def analyze_feedback():
    data = request.json
    feedback_text = data.get('feedback', '')

    if not feedback_text:
        return jsonify({"error": "No feedback text provided"}), 400

    # Example sentiment analysis
    blob = TextBlob(feedback_text)
    score = blob.sentiment.polarity
    if score > 0.1:
        label = "positive"
    elif score < -0.1:
        label = "negative"
    else:
        label = "neutral"

    return jsonify({
        "sentiment_score": score,
        "sentiment_label": label
    })

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000)
