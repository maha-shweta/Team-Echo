import mysql.connector
from textblob import TextBlob

# 1. Connect to your database
db = mysql.connector.connect(
    host="localhost",
    user="root",
    password="",
    database="team_echo_new"
)
cursor = db.cursor()

# 2. Find feedback that hasn't been analyzed yet
cursor.execute("SELECT feedback_id, feedback_text FROM feedback WHERE sentiment_label IS NULL")
rows = cursor.fetchall()

for row in rows:
    f_id, text = row[0], row[1]
    
    # 3. Analyze the text (AI part)
    # This gives a score from -1 (very negative) to +1 (very positive)
    analysis = TextBlob(text)
    score = analysis.sentiment.polarity 
    
    if score > 0.1:
        label = 'Positive'
    elif score < -0.1:
        label = 'Negative'
    else:
        label = 'Neutral'
    
    # 4. Save the results back to your SQL table
    cursor.execute("UPDATE feedback SET sentiment_score = %s, sentiment_label = %s WHERE feedback_id = %s", 
                   (score, label, f_id))

db.commit()
print(f"Successfully analyzed {len(rows)} feedbacks!")