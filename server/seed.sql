INSERT INTO users (name, email, student_id, role, password_hash) VALUES
('Course Instructor', 'teacher@example.com', NULL, 'admin', '$2y$12$7cwVXvvfAoQXeaM0rfutxuzgd.5AVVxmgBo2XkZbKW80AvzHD4YCW'),
('Ali Hassan', '202101234@stu.uob.edu.bh', '202101234', 'student', '$2y$12$7cwVXvvfAoQXeaM0rfutxuzgd.5AVVxmgBo2XkZbKW80AvzHD4YCW'),
('Fatema Ahmed', '202205678@stu.uob.edu.bh', '202205678', 'student', '$2y$12$7cwVXvvfAoQXeaM0rfutxuzgd.5AVVxmgBo2XkZbKW80AvzHD4YCW');

INSERT INTO resources (title, description, link, created_by) VALUES
('Syllabus', 'Course overview and policies.', 'https://uob.edu/syllabus', 1),
('Week 1 Slides', 'Introductory lecture slides.', 'https://uob.edu/week1', 1);

INSERT INTO weekly_entries (week_number, title, description, notes, links, created_by) VALUES
(1, 'Introduction to HTML/CSS', 'Overview of the web platform.', 'Read MDN introduction.', 'https://developer.mozilla.org', 1);

INSERT INTO assignments (title, description, due_date, attachment_url, created_by) VALUES
('Assignment 1', 'Build a semantic home page.', '2025-09-15', 'https://uob.edu/assignment1.pdf', 1);

INSERT INTO discussion_topics (subject, body, user_id) VALUES
('Welcome!', 'Introduce yourself to the class.', 1);
