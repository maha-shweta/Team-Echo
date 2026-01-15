<?php
// includes/word_cloud.php

// 1. Fetch all feedback text from your database
$query = "SELECT feedback_text FROM feedback";
$result = $conn->query($query);

$all_text = "";
while ($row = $result->fetch_assoc()) {
    $all_text .= " " . $row['feedback_text'];
}

// 2. Clean and tokenize the text
$all_text = strtolower($all_text);
// Remove punctuation
$clean_text = preg_replace('/[^\w\s]/', '', $all_text);
$words = str_word_count($clean_text, 1);

// 3. Define "Stop Words" to ignore (Add Banglish/Common words here)
$stop_words = [
    'the', 'and', 'is', 'to', 'in', 'it', 'of', 'for', 'with', 'this', 
    'you', 'that', 'was', 'on', 'are', 'please', 'bro', 'hi', 'kemon', 'ekta'
];

// 4. Filter words and count frequency
$filtered_words = array_diff($words, $stop_words);
$word_counts = array_count_values($filtered_words);

// Sort so the most frequent words are at the top
arsort($word_counts);

// 5. Display the Cloud
echo '<div class="word-cloud-container" style="padding: 20px; background: #fff; border-radius: 10px; text-align: center; border: 1px solid #ddd;">';
echo '<h3 style="margin-bottom: 15px; color: #333;">🔥 Trending Issues</h3>';

// Only show the top 20 words
$top_words = array_slice($word_counts, 0, 20);

foreach ($top_words as $word => $count) {
    // Calculate font size: Base 14px + (frequency * factor)
    $font_size = 14 + ($count * 5); 
    // Random colors for a professional look
    $colors = ['#3498db', '#e67e22', '#2ecc71', '#9b59b6', '#34495e'];
    $random_color = $colors[array_rand($colors)];
    
    echo "<span style='font-size: {$font_size}px; color: {$random_color}; margin: 10px; display: inline-block; font-weight: bold; cursor: pointer;' title='Mentioned {$count} times'>#$word</span>";
}

echo '</div>';
?>