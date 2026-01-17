import mysql.connector
from textblob import TextBlob
import sys

# 1. Connect to your database
try:
    db = mysql.connector.connect(
        host="localhost",
        user="root",
        password="",
        database="team_echo_final"
    )
    cursor = db.cursor()
    print("✓ Database connection successful")
except Exception as e:
    print(f"✗ Database connection failed: {e}")
    sys.exit(1)

# 2. Find feedback that hasn't been analyzed yet
try:
    # Query for unanalyzed feedback
    query = "SELECT feedback_id, feedback_text FROM feedback WHERE sentiment_label IS NULL OR sentiment_label = ''"
    cursor.execute(query)
    rows = cursor.fetchall()
    
    print(f"✓ Found {len(rows)} feedbacks to analyze")
    
    if len(rows) == 0:
        print("\nℹ No new feedbacks to analyze.")
        
        # Check total feedback count
        cursor.execute("SELECT COUNT(*) FROM feedback")
        total = cursor.fetchone()[0]
        print(f"ℹ Total feedbacks in database: {total}")
        
        # Check already analyzed
        cursor.execute("SELECT COUNT(*) FROM feedback WHERE sentiment_label IS NOT NULL AND sentiment_label != ''")
        analyzed = cursor.fetchone()[0]
        print(f"ℹ Already analyzed: {analyzed}")
        
        if total == 0:
            print("\n⚠ WARNING: No feedbacks found in database!")
            print("Please add some feedbacks first.")
        
except Exception as e:
    print(f"✗ Query error: {e}")
    db.close()
    sys.exit(1)

# 3. Analyze each feedback
analyzed_count = 0
print("\n" + "="*60)
print("Starting analysis...")
print("="*60 + "\n")

for row in rows:
    try:
        f_id, text = row[0], row[1]
        
        # Skip empty or null text
        if not text or text.strip() == "":
            print(f"⊘ Skipping feedback #{f_id} (empty text)")
            continue
        
        # Analyze the text using TextBlob
        analysis = TextBlob(str(text))
        score = analysis.sentiment.polarity 
        
        # Determine sentiment label
        if score > 0.1:
            label = 'Positive'
            emoji = '😊'
        elif score < -0.1:
            label = 'Negative'
            emoji = '😞'
        else:
            label = 'Neutral'
            emoji = '😐'
        
        # Save the results back to database
        update_query = "UPDATE feedback SET sentiment_score = %s, sentiment_label = %s WHERE feedback_id = %s"
        cursor.execute(update_query, (score, label, f_id))
        
        # Truncate text for display
        display_text = text[:50] + "..." if len(text) > 50 else text
        print(f"{emoji} Feedback #{f_id}: {label} (score: {score:.2f})")
        print(f"   Text: \"{display_text}\"")
        print()
        
        analyzed_count += 1
        
    except Exception as e:
        print(f"✗ Error analyzing feedback #{f_id}: {e}\n")
        continue

# Commit changes and close connection
db.commit()
db.close()

# Final summary
print("="*60)
if analyzed_count > 0:
    print(f"✓ Successfully analyzed {analyzed_count} feedback(s)!")
else:
    print("⊘ No feedbacks were analyzed.")
print("="*60)
