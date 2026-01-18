# Team-Echo
Anonymous Feedback System

#### (Mahashweta)

## User Authentication and Authorization
- User login system to ensure only authorized users can access the platform.
- Role-based access control (Admin, HR, and User roles).

## Feedback Submission
- Users can submit feedback with a selected category, feedback text, and optional file attachments.
- Ability to submit feedback anonymously.
- Option to select multiple tags for feedback categorization.

## Feedback Management
- Admin and HR roles can manage feedback by changing priorities (Low, Medium, High, Critical).
- Feedback can be marked as resolved by Admin/HR. 
- Feedback records can be exported to CSV for analysis.
- Feedback management through admin dashboard (view, manage, and resolve).

## Tags Management
- Admin/HR can manage tags by assigning them to feedback.
- Tags can be removed or added to feedback by Admin/HR.
- Feedback can have multiple tags assigned based on relevance.

## Voting on Feedback
- Users can upvote or downvote feedback.
- Feedback voting data (upvotes and downvotes) are tracked.
- Users can update their votes (change from upvote to downvote and vice versa).

## Commenting and Threaded Discussions  
- Users can comment on feedback.
- Admin and HR can leave internal comments that are visible only to HR and Admin.
- Feedback comments support threaded replies (replying to specific comments).   
- Comment editing and deletion for Admin/HR roles.
- Timestamp and author information for comments.

## File Attachments
- Users can upload multiple file attachments (e.g., images, documents) when submitting feedback.
- Attachments are saved and stored on the server.
- Admin/HR can view the attached files related to feedback.

## Feedback Resolution
- Admin/HR can mark feedback as resolved once it's addressed.
- Feedback resolution status is visible in the feedback details.

## Export Data
- Admin can export feedback data (including priority, category, resolution status, and more) as a CSV file.
- All feedback data is fetched and organized for exporting.

## Feedback Analytics 
- All users can access an analytics dashboard to monitor feedback trends.
- Option to filter and analyze feedback based on categories, priorities, status, etc.

## Comments Section
- Comments can be added to feedback by users, with internal or public options.
- Users can reply to comments, creating a conversation thread.
- Comment deletion (if permitted) and editing for users within time constraints. 

## Internal Commenting for HR/Admin
- HR/Admin roles have the option to make internal comments that are only visible to HR and Admin.



#### (Rishta)
##  AI & NLP Integration

###  Smart Chatbot Assistant
The system features an integrated **AI Chatbot** designed to enhance user engagement and streamline the feedback process:
* **Real-time Assistance:** Acts as a 24/7 guide, helping users navigate the platform and answering common questions.
* **Interactive UI:** Seamlessly integrated into the bottom-right corner using a modern, floating interface.
* **Pre-screening:** Encourages users to provide more detailed feedback before submission.

###  Python-Powered Sentiment Analysis
The "Team-Echo AI" engine bridges **PHP** and **Python (NLTK)** to understand the emotional "temperature" of the organization:

* **Natural Language Processing (NLP):** Every piece of feedback is processed through the **VADER (Valence Aware Dictionary and sEntiment Reasoner)** lexicon to calculate emotional intensity.
* **Team Mood Tracking:** Aggregates individual scores into a "Team Mood" percentage bar, allowing HR to see general satisfaction levels at a glance.
* **Visual Data Labeling:** Automatically color-codes feedback:
    * 🟢 **Positive:** High satisfaction.
    * 🟡 **Neutral:** Informational/Balanced.
    * 🔴 **Negative:** Urgent issues requiring attention.

###  Technical Workflow
1. **PHP** retrieves raw feedback from the **MySQL** database.
2. The text is passed to a **Python** script via `shell_exec()`.
3. The **NLTK Library** analyzes the text and returns a JSON sentiment score.
4. The dashboard renders the data into a visual "Mood Bar" and categorized feedback cards.




Frontend Implementation 

#### (Mehenaz)
##  Frontend Implementation 

###  User Authentication & Authorization UI
1. Login interface for registered users
2.Role-based dashboard redirection (User / Admin / HR)
3.Secure session handling via PHP integration

###  Feedback Submission Interface
1.Category selection dropdown
2.Multi-tag selection for classification
3.Anonymous submission form
4.File attachment upload UI
5.Real-time validation and error prompts

###  Feedback Display & Interaction
1.Feedback cards with category, tags, priority, and status
2.Voting buttons (Upvote / Downvote)
3.Dynamic vote count updates
4.Keyword cloud visualization section

###  Commenting System UI
1.Public comment section under each feedback
2.Threaded replies
3.Timestamp & author display
4.Edit/Delete options (Admin/HR restricted)
5.Internal comments section visible only to Admin & HR

###  Admin & HR Dashboard UI
1.Feedback management panel
2.Priority change dropdown (Low → Critical)
3.Resolve feedback action button
4.Tag assignment and removal interface
5.CSV export trigger button

###  Attachments Viewer
1.File preview/download options for uploaded feedback files
2.Organized attachment display per feedback


###  Feedback Analytics Dashboard
1.Category-based filters
2.Status and priority filters
3.Visual charts and feedback summaries
4.Keyword cloud display for trend understanding


###  Frontend Technologies Used
HTML5
CSS3
JavaScript














