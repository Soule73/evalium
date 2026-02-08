<?php

declare(strict_types=1);

return [
    'page_titles' => [
        'index' => 'Grading',
        'show' => 'Grade Student',
    ],

    'index' => [
        'title' => 'Grading - :assessment',
        'section_title' => 'Student Submissions',
        'section_subtitle' => ':count submission(s) to review',
        'student_label' => 'Student',
        'status_label' => 'Status',
        'score_label' => 'Score',
        'submitted_at' => 'Submitted At',
        'actions_label' => 'Actions',
        'not_submitted' => 'Not Submitted',
        'graded' => 'Graded',
        'pending' => 'Pending Grading',
        'grade' => 'Grade',
        'review' => 'Review',
        'back_to_assessment' => 'Back to Assessment',
        'empty_title' => 'No submissions yet',
        'empty_subtitle' => 'Students haven\'t submitted their assessments yet.',
    ],

    'show' => [
        'title' => 'Grading - :student - :assessment',
        'correction_title' => 'Grading :student',
        'back_to_grading' => 'Back to Grading',
        'back_to_assessment' => 'Back to Assessment',
        'save_grades' => 'Save Grades',
        'saving' => 'Saving...',
        'total_score' => 'Total Score',
        'percentage' => 'Percentage',
        'status' => 'Status',
        'submitted' => 'Submitted',
        'not_submitted' => 'Not Submitted',
        'questions_correction' => 'Questions & Grading',
        'question_score_label' => 'Score (max :max)',
        'auto_graded_info' => 'Automatically graded',
        'manual_grading_required' => 'Manual grading required',
        'question_comment_label' => 'Feedback',
        'question_comment_placeholder' => 'Add feedback for this answer...',
        'teacher_notes_label' => 'General Notes',
        'teacher_notes_placeholder' => 'Add general notes for the student...',
        'teacher_notes_help' => 'These notes will be visible to the student',
        'confirm_save_title' => 'Save Grades',
        'confirm_save_message' => 'Save grades and feedback for :student?',
        'confirm_save' => 'Save',
        'cancel' => 'Cancel',
    ],

    'review' => [
        'title' => 'Review - :student - :assessment',
        'result_title' => 'Results for :student',
        'questions_review' => 'Questions & Answers',
        'score_obtained' => 'Score obtained',
        'teacher_feedback' => 'Teacher feedback',
        'edit_grades' => 'Edit Grades',
        'graded_at' => 'Graded on',
    ],
];
