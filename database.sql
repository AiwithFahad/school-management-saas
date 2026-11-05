-- EduPro — School Management SaaS — Complete MySQL Schema (MySQL 8+, utf8mb4)
-- Demo password for all seeded users: password
-- Hash below is a PHP password_hash()/bcrypt hash of "password" (verifiable with password_verify).
CREATE DATABASE IF NOT EXISTS school_saas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE school_saas;
SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS audit_logs,activities,notifications,support_tickets,announcements,settings,password_resets,parent_student,homework,transport_assignments,routes,vehicles,book_issues,books,timetable,exam_marks,exams,fee_invoices,fee_structures,attendance,class_subjects,teachers,students,subjects,sections,classes,academic_sessions,invoices,payments,subscriptions,coupons,plans,role_permissions,permissions,roles,users,tenants;
SET FOREIGN_KEY_CHECKS=1;

CREATE TABLE tenants (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(150) NOT NULL, slug VARCHAR(160) NOT NULL UNIQUE, status ENUM('active','suspended') NOT NULL DEFAULT 'active', created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX idx_tenants_status(status)) ENGINE=InnoDB;
CREATE TABLE roles (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(60) NOT NULL UNIQUE, label VARCHAR(100) NOT NULL) ENGINE=InnoDB;
CREATE TABLE permissions (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(80) NOT NULL UNIQUE, label VARCHAR(120) NOT NULL) ENGINE=InnoDB;
CREATE TABLE role_permissions (role_id INT UNSIGNED NOT NULL, permission_id INT UNSIGNED NOT NULL, PRIMARY KEY(role_id,permission_id), FOREIGN KEY(role_id) REFERENCES roles(id) ON DELETE CASCADE, FOREIGN KEY(permission_id) REFERENCES permissions(id) ON DELETE CASCADE) ENGINE=InnoDB;
CREATE TABLE users (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, tenant_id INT UNSIGNED NULL, name VARCHAR(120) NOT NULL, email VARCHAR(190) NOT NULL UNIQUE, password_hash VARCHAR(255) NOT NULL, role ENUM('super_admin','school_admin','principal','teacher','accountant','student','parent') NOT NULL DEFAULT 'teacher', status ENUM('active','suspended') NOT NULL DEFAULT 'active', two_factor_secret VARCHAR(120) NULL, remember_token VARCHAR(64) NULL, email_verified_at DATETIME NULL, last_login_at DATETIME NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX idx_users_tenant(tenant_id), CONSTRAINT fk_users_tenant FOREIGN KEY(tenant_id) REFERENCES tenants(id) ON DELETE CASCADE) ENGINE=InnoDB;
CREATE TABLE password_resets (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, email VARCHAR(190) NOT NULL, token VARCHAR(64) NOT NULL, expires_at DATETIME NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX idx_pr_email(email), INDEX idx_pr_token(token)) ENGINE=InnoDB;
CREATE TABLE plans (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(80) NOT NULL, slug VARCHAR(80) NOT NULL UNIQUE, price_monthly DECIMAL(10,2) NOT NULL DEFAULT 0, price_yearly DECIMAL(10,2) NOT NULL DEFAULT 0, max_users INT NOT NULL DEFAULT 10, max_students INT NOT NULL DEFAULT 100, max_teachers INT NOT NULL DEFAULT 10, storage_mb INT NOT NULL DEFAULT 500, features TEXT NULL, is_active TINYINT(1) NOT NULL DEFAULT 1, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB;
CREATE TABLE subscriptions (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, tenant_id INT UNSIGNED NOT NULL, plan_id INT UNSIGNED NOT NULL, status ENUM('trial','active','cancelled','expired') NOT NULL DEFAULT 'trial', billing_cycle ENUM('monthly','yearly') NOT NULL DEFAULT 'monthly', starts_at DATETIME NULL, ends_at DATETIME NULL, trial_ends_at DATETIME NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX idx_sub_tenant(tenant_id), FOREIGN KEY(tenant_id) REFERENCES tenants(id) ON DELETE CASCADE, FOREIGN KEY(plan_id) REFERENCES plans(id)) ENGINE=InnoDB;
CREATE TABLE payments (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, tenant_id INT UNSIGNED NULL, subscription_id INT UNSIGNED NULL, student_id INT UNSIGNED NULL, fee_invoice_id INT UNSIGNED NULL, amount DECIMAL(10,2) NOT NULL DEFAULT 0, status ENUM('paid','pending','failed','refunded') NOT NULL DEFAULT 'paid', method VARCHAR(40) NULL, paid_at DATETIME NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX idx_pay_tenant(tenant_id)) ENGINE=InnoDB;
CREATE TABLE invoices (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, tenant_id INT UNSIGNED NOT NULL, payment_id INT UNSIGNED NULL, invoice_number VARCHAR(40) NOT NULL, amount DECIMAL(10,2) NOT NULL DEFAULT 0, status ENUM('paid','unpaid','cancelled') NOT NULL DEFAULT 'paid', issued_at DATETIME NULL, INDEX idx_inv_tenant(tenant_id), FOREIGN KEY(tenant_id) REFERENCES tenants(id) ON DELETE CASCADE) ENGINE=InnoDB;
CREATE TABLE coupons (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, code VARCHAR(40) NOT NULL UNIQUE, discount_percent INT NOT NULL DEFAULT 10, max_uses INT NOT NULL DEFAULT 100, used_count INT NOT NULL DEFAULT 0, expires_at DATETIME NULL, is_active TINYINT(1) NOT NULL DEFAULT 1, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB;
CREATE TABLE support_tickets (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, tenant_id INT UNSIGNED NULL, user_id INT UNSIGNED NULL, subject VARCHAR(190) NOT NULL, message TEXT NULL, status ENUM('open','answered','closed') NOT NULL DEFAULT 'open', created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB;
CREATE TABLE announcements (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, title VARCHAR(190) NOT NULL, body TEXT NULL, is_active TINYINT(1) NOT NULL DEFAULT 1, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB;
CREATE TABLE settings (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, tenant_id INT UNSIGNED NOT NULL DEFAULT 0, setting_key VARCHAR(80) NOT NULL, setting_value TEXT NULL, UNIQUE KEY uq_settings(tenant_id,setting_key)) ENGINE=InnoDB;
CREATE TABLE audit_logs (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, tenant_id INT UNSIGNED NULL, user_id INT UNSIGNED NULL, action VARCHAR(120) NOT NULL, entity VARCHAR(80) NULL, entity_id INT NULL, ip_address VARCHAR(45) NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX idx_audit_tenant(tenant_id)) ENGINE=InnoDB;
CREATE TABLE activities (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, tenant_id INT UNSIGNED NULL, user_id INT UNSIGNED NULL, description VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX idx_act_tenant(tenant_id)) ENGINE=InnoDB;
CREATE TABLE notifications (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, tenant_id INT UNSIGNED NULL, user_id INT UNSIGNED NULL, title VARCHAR(190) NOT NULL, body TEXT NULL, is_read TINYINT(1) NOT NULL DEFAULT 0, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB;

CREATE TABLE academic_sessions (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, tenant_id INT UNSIGNED NOT NULL, name VARCHAR(40) NOT NULL, start_date DATE NULL, end_date DATE NULL, is_current TINYINT(1) NOT NULL DEFAULT 0, INDEX idx_sess_tenant(tenant_id), FOREIGN KEY(tenant_id) REFERENCES tenants(id) ON DELETE CASCADE) ENGINE=InnoDB;
CREATE TABLE teachers (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, tenant_id INT UNSIGNED NOT NULL, user_id INT UNSIGNED NULL, staff_id VARCHAR(40) NOT NULL, name VARCHAR(120) NOT NULL, department VARCHAR(80) NULL, email VARCHAR(190) NULL, phone VARCHAR(40) NULL, salary DECIMAL(10,2) NOT NULL DEFAULT 0, status ENUM('active','on_leave','resigned') NOT NULL DEFAULT 'active', created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY uq_teacher_staff(tenant_id,staff_id), INDEX idx_teach_tenant(tenant_id), FOREIGN KEY(tenant_id) REFERENCES tenants(id) ON DELETE CASCADE) ENGINE=InnoDB;
CREATE TABLE classes (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, tenant_id INT UNSIGNED NOT NULL, name VARCHAR(80) NOT NULL, numeric_name INT NOT NULL DEFAULT 0, class_teacher_id INT UNSIGNED NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX idx_class_tenant(tenant_id), FOREIGN KEY(tenant_id) REFERENCES tenants(id) ON DELETE CASCADE, FOREIGN KEY(class_teacher_id) REFERENCES teachers(id) ON DELETE SET NULL) ENGINE=InnoDB;
CREATE TABLE sections (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, tenant_id INT UNSIGNED NOT NULL, class_id INT UNSIGNED NOT NULL, name VARCHAR(20) NOT NULL, capacity INT NOT NULL DEFAULT 40, UNIQUE KEY uq_section(tenant_id,class_id,name), FOREIGN KEY(tenant_id) REFERENCES tenants(id) ON DELETE CASCADE, FOREIGN KEY(class_id) REFERENCES classes(id) ON DELETE CASCADE) ENGINE=InnoDB;
CREATE TABLE subjects (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, tenant_id INT UNSIGNED NOT NULL, class_id INT UNSIGNED NULL, name VARCHAR(80) NOT NULL, code VARCHAR(20) NULL, INDEX idx_subj_tenant(tenant_id), FOREIGN KEY(tenant_id) REFERENCES tenants(id) ON DELETE CASCADE, FOREIGN KEY(class_id) REFERENCES classes(id) ON DELETE SET NULL) ENGINE=InnoDB;
CREATE TABLE students (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, tenant_id INT UNSIGNED NOT NULL, user_id INT UNSIGNED NULL, admission_no VARCHAR(40) NOT NULL, first_name VARCHAR(80) NOT NULL, last_name VARCHAR(80) NOT NULL DEFAULT '', gender ENUM('male','female','other') NOT NULL DEFAULT 'male', date_of_birth DATE NULL, class_id INT UNSIGNED NULL, section_id INT UNSIGNED NULL, category VARCHAR(40) NOT NULL DEFAULT 'general', guardian_name VARCHAR(120) NULL, guardian_phone VARCHAR(40) NULL, guardian_email VARCHAR(190) NULL, address VARCHAR(255) NULL, status ENUM('active','transferred','graduated','suspended') NOT NULL DEFAULT 'active', created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY uq_student_adm(tenant_id,admission_no), INDEX idx_stu_tenant(tenant_id), INDEX idx_stu_class(class_id), FOREIGN KEY(tenant_id) REFERENCES tenants(id) ON DELETE CASCADE, FOREIGN KEY(class_id) REFERENCES classes(id) ON DELETE SET NULL, FOREIGN KEY(section_id) REFERENCES sections(id) ON DELETE SET NULL) ENGINE=InnoDB;
CREATE TABLE class_subjects (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, tenant_id INT UNSIGNED NOT NULL, class_id INT UNSIGNED NOT NULL, subject_id INT UNSIGNED NOT NULL, teacher_id INT UNSIGNED NULL, UNIQUE KEY uq_cs(tenant_id,class_id,subject_id), FOREIGN KEY(tenant_id) REFERENCES tenants(id) ON DELETE CASCADE, FOREIGN KEY(class_id) REFERENCES classes(id) ON DELETE CASCADE, FOREIGN KEY(subject_id) REFERENCES subjects(id) ON DELETE CASCADE, FOREIGN KEY(teacher_id) REFERENCES teachers(id) ON DELETE SET NULL) ENGINE=InnoDB;
CREATE TABLE attendance (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, tenant_id INT UNSIGNED NOT NULL, student_id INT UNSIGNED NOT NULL, class_id INT UNSIGNED NULL, att_date DATE NOT NULL, status ENUM('present','absent','late') NOT NULL DEFAULT 'present', marked_by INT UNSIGNED NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, UNIQUE KEY uq_att(tenant_id,student_id,att_date), INDEX idx_att_class_date(class_id,att_date), FOREIGN KEY(tenant_id) REFERENCES tenants(id) ON DELETE CASCADE, FOREIGN KEY(student_id) REFERENCES students(id) ON DELETE CASCADE) ENGINE=InnoDB;
CREATE TABLE fee_structures (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, tenant_id INT UNSIGNED NOT NULL, name VARCHAR(120) NOT NULL, class_id INT UNSIGNED NULL, amount DECIMAL(10,2) NOT NULL DEFAULT 0, frequency ENUM('monthly','term','annual','one_time') NOT NULL DEFAULT 'monthly', created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY(tenant_id) REFERENCES tenants(id) ON DELETE CASCADE, FOREIGN KEY(class_id) REFERENCES classes(id) ON DELETE SET NULL) ENGINE=InnoDB;
CREATE TABLE fee_invoices (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, tenant_id INT UNSIGNED NOT NULL, student_id INT UNSIGNED NOT NULL, structure_id INT UNSIGNED NULL, period VARCHAR(20) NOT NULL DEFAULT '', amount_due DECIMAL(10,2) NOT NULL DEFAULT 0, amount_paid DECIMAL(10,2) NOT NULL DEFAULT 0, status ENUM('unpaid','partial','paid') NOT NULL DEFAULT 'unpaid', due_date DATE NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, INDEX idx_fi_tenant(tenant_id), INDEX idx_fi_student(student_id), FOREIGN KEY(tenant_id) REFERENCES tenants(id) ON DELETE CASCADE, FOREIGN KEY(student_id) REFERENCES students(id) ON DELETE CASCADE, FOREIGN KEY(structure_id) REFERENCES fee_structures(id) ON DELETE SET NULL) ENGINE=InnoDB;
CREATE TABLE exams (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, tenant_id INT UNSIGNED NOT NULL, name VARCHAR(120) NOT NULL, class_id INT UNSIGNED NULL, term VARCHAR(60) NULL, start_date DATE NULL, end_date DATE NULL, max_marks INT NOT NULL DEFAULT 100, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY(tenant_id) REFERENCES tenants(id) ON DELETE CASCADE, FOREIGN KEY(class_id) REFERENCES classes(id) ON DELETE SET NULL) ENGINE=InnoDB;
CREATE TABLE exam_marks (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, tenant_id INT UNSIGNED NOT NULL, exam_id INT UNSIGNED NOT NULL, student_id INT UNSIGNED NOT NULL, subject_id INT UNSIGNED NOT NULL, marks_obtained DECIMAL(6,2) NOT NULL DEFAULT 0, UNIQUE KEY uq_marks(tenant_id,exam_id,student_id,subject_id), FOREIGN KEY(tenant_id) REFERENCES tenants(id) ON DELETE CASCADE, FOREIGN KEY(exam_id) REFERENCES exams(id) ON DELETE CASCADE, FOREIGN KEY(student_id) REFERENCES students(id) ON DELETE CASCADE, FOREIGN KEY(subject_id) REFERENCES subjects(id) ON DELETE CASCADE) ENGINE=InnoDB;
CREATE TABLE timetable (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, tenant_id INT UNSIGNED NOT NULL, class_id INT UNSIGNED NOT NULL, subject_id INT UNSIGNED NULL, teacher_id INT UNSIGNED NULL, day_of_week TINYINT NOT NULL, period_no INT NOT NULL DEFAULT 1, start_time TIME NULL, end_time TIME NULL, room VARCHAR(40) NULL, INDEX idx_tt_class(class_id,day_of_week), FOREIGN KEY(tenant_id) REFERENCES tenants(id) ON DELETE CASCADE, FOREIGN KEY(class_id) REFERENCES classes(id) ON DELETE CASCADE) ENGINE=InnoDB;
CREATE TABLE books (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, tenant_id INT UNSIGNED NOT NULL, title VARCHAR(190) NOT NULL, author VARCHAR(120) NULL, isbn VARCHAR(40) NULL, category VARCHAR(80) NULL, copies_total INT NOT NULL DEFAULT 1, copies_available INT NOT NULL DEFAULT 1, INDEX idx_books_tenant(tenant_id), FOREIGN KEY(tenant_id) REFERENCES tenants(id) ON DELETE CASCADE) ENGINE=InnoDB;
CREATE TABLE book_issues (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, tenant_id INT UNSIGNED NOT NULL, book_id INT UNSIGNED NOT NULL, student_id INT UNSIGNED NOT NULL, issue_date DATE NOT NULL, due_date DATE NOT NULL, return_date DATE NULL, fine DECIMAL(8,2) NOT NULL DEFAULT 0, status ENUM('issued','returned') NOT NULL DEFAULT 'issued', FOREIGN KEY(tenant_id) REFERENCES tenants(id) ON DELETE CASCADE, FOREIGN KEY(book_id) REFERENCES books(id) ON DELETE CASCADE, FOREIGN KEY(student_id) REFERENCES students(id) ON DELETE CASCADE) ENGINE=InnoDB;
CREATE TABLE vehicles (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, tenant_id INT UNSIGNED NOT NULL, vehicle_no VARCHAR(40) NOT NULL, model VARCHAR(80) NULL, driver_name VARCHAR(120) NULL, driver_phone VARCHAR(40) NULL, capacity INT NOT NULL DEFAULT 40, FOREIGN KEY(tenant_id) REFERENCES tenants(id) ON DELETE CASCADE) ENGINE=InnoDB;
CREATE TABLE routes (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, tenant_id INT UNSIGNED NOT NULL, name VARCHAR(120) NOT NULL, vehicle_id INT UNSIGNED NULL, monthly_fee DECIMAL(10,2) NOT NULL DEFAULT 0, stops TEXT NULL, FOREIGN KEY(tenant_id) REFERENCES tenants(id) ON DELETE CASCADE, FOREIGN KEY(vehicle_id) REFERENCES vehicles(id) ON DELETE SET NULL) ENGINE=InnoDB;
CREATE TABLE transport_assignments (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, tenant_id INT UNSIGNED NOT NULL, student_id INT UNSIGNED NOT NULL, route_id INT UNSIGNED NOT NULL, pickup_point VARCHAR(120) NULL, UNIQUE KEY uq_ta(tenant_id,student_id), FOREIGN KEY(tenant_id) REFERENCES tenants(id) ON DELETE CASCADE, FOREIGN KEY(student_id) REFERENCES students(id) ON DELETE CASCADE, FOREIGN KEY(route_id) REFERENCES routes(id) ON DELETE CASCADE) ENGINE=InnoDB;
CREATE TABLE homework (id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, tenant_id INT UNSIGNED NOT NULL, class_id INT UNSIGNED NOT NULL, subject_id INT UNSIGNED NULL, teacher_id INT UNSIGNED NULL, title VARCHAR(190) NOT NULL, description TEXT NULL, due_date DATE NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY(tenant_id) REFERENCES tenants(id) ON DELETE CASCADE, FOREIGN KEY(class_id) REFERENCES classes(id) ON DELETE CASCADE) ENGINE=InnoDB;
CREATE TABLE parent_student (tenant_id INT UNSIGNED NOT NULL, parent_user_id INT UNSIGNED NOT NULL, student_id INT UNSIGNED NOT NULL, PRIMARY KEY(parent_user_id,student_id), FOREIGN KEY(tenant_id) REFERENCES tenants(id) ON DELETE CASCADE, FOREIGN KEY(parent_user_id) REFERENCES users(id) ON DELETE CASCADE, FOREIGN KEY(student_id) REFERENCES students(id) ON DELETE CASCADE) ENGINE=InnoDB;

-- ===== Seeds =====
INSERT INTO roles(name,label) VALUES ('super_admin','Super Admin'),('school_admin','School Admin'),('principal','Principal'),('teacher','Teacher'),('accountant','Accountant'),('student','Student'),('parent','Parent');
INSERT INTO permissions(name,label) VALUES ('manage_students','Manage students'),('manage_teachers','Manage teachers'),('manage_fees','Manage fees'),('mark_attendance','Mark attendance'),('enter_marks','Enter marks'),('view_reports','View reports');
INSERT INTO role_permissions(role_id,permission_id) SELECT r.id,p.id FROM roles r CROSS JOIN permissions p WHERE r.name IN ('school_admin','principal');
INSERT INTO role_permissions(role_id,permission_id) SELECT r.id,p.id FROM roles r JOIN permissions p ON p.name IN ('mark_attendance','enter_marks','view_reports') WHERE r.name='teacher';
INSERT INTO role_permissions(role_id,permission_id) SELECT r.id,p.id FROM roles r JOIN permissions p ON p.name IN ('manage_fees','view_reports') WHERE r.name='accountant';
INSERT INTO plans(name,slug,price_monthly,price_yearly,max_users,max_students,max_teachers,storage_mb,features) VALUES
('Free Trial','free-trial',0,0,10,100,10,500,'Core modules, 100 students'),
('Basic','basic',19,190,25,500,25,2000,'All core modules, library & transport'),
('Standard','standard',49,490,60,2000,60,10000,'Everything in Basic + exams, reports, portals'),
('Premium','premium',99,990,999,9999,999,50000,'Unlimited scale, priority support');
INSERT INTO coupons(code,discount_percent,max_uses,expires_at) VALUES ('WELCOME10',10,100,DATE_ADD(NOW(),INTERVAL 1 YEAR));
INSERT INTO tenants(id,name,slug,status) VALUES (1,'Demo School','demo-school','active');
INSERT INTO users(id,tenant_id,name,email,password_hash,role,status,email_verified_at) VALUES
(1,NULL,'Platform Owner','super@edupro.test','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','super_admin','active',NOW()),
(2,1,'Demo School Admin','admin@demo.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','school_admin','active',NOW()),
(3,1,'Principal Demo','principal@demo.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','principal','active',NOW()),
(4,1,'Ayesha Khan','teacher@demo.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','teacher','active',NOW()),
(5,1,'Accounts Demo','accountant@demo.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','accountant','active',NOW()),
(6,1,'Ali Raza (Student)','student@demo.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','student','active',NOW()),
(7,1,'Raza Parent','parent@demo.com','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','parent','active',NOW());
INSERT INTO subscriptions(tenant_id,plan_id,status,billing_cycle,starts_at,trial_ends_at) VALUES (1,3,'trial','monthly',NOW(),DATE_ADD(NOW(),INTERVAL 14 DAY));
INSERT INTO academic_sessions(id,tenant_id,name,start_date,end_date,is_current) VALUES (1,1,'2026-27',CURDATE(),DATE_ADD(CURDATE(),INTERVAL 1 YEAR),1);
INSERT INTO teachers(id,tenant_id,user_id,staff_id,name,department,email,phone,salary) VALUES
(1,1,4,'TCH-001','Ayesha Khan','Mathematics','teacher@demo.com','+92-300-1111111',85000),
(2,1,NULL,'TCH-002','Bilal Ahmed','Science','bilal@demo.com','+92-300-2222222',80000),
(3,1,NULL,'TCH-003','Sara Malik','English','sara@demo.com','+92-300-3333333',78000),
(4,1,NULL,'TCH-004','Usman Tariq','Computing','usman@demo.com','+92-300-4444444',82000);
INSERT INTO classes(id,tenant_id,name,numeric_name,class_teacher_id) VALUES (1,1,'Grade 5',5,1),(2,1,'Grade 6',6,2),(3,1,'Grade 7',7,3);
INSERT INTO sections(id,tenant_id,class_id,name,capacity) VALUES (1,1,1,'A',40),(2,1,1,'B',40),(3,1,2,'A',40),(4,1,3,'A',40);
INSERT INTO subjects(id,tenant_id,class_id,name,code) VALUES (1,1,1,'Mathematics','MTH5'),(2,1,1,'English','ENG5'),(3,1,1,'Science','SCI5'),(4,1,2,'Mathematics','MTH6'),(5,1,2,'English','ENG6'),(6,1,2,'Urdu','URD6'),(7,1,NULL,'Computer Science','CS');
INSERT INTO class_subjects(tenant_id,class_id,subject_id,teacher_id) VALUES (1,1,1,1),(1,1,2,3),(1,1,3,2),(1,2,4,1),(1,2,5,3),(1,2,6,4),(1,3,7,4);
INSERT INTO students(id,tenant_id,user_id,admission_no,first_name,last_name,gender,date_of_birth,class_id,section_id,category,guardian_name,guardian_phone,guardian_email) VALUES
(1,1,6,'ADM-001','Ali','Raza','male','2015-03-12',1,1,'general','Imran Raza','+92-321-1000001','parent@demo.com'),
(2,1,NULL,'ADM-002','Fatima','Raza','female','2015-07-02',1,1,'general','Imran Raza','+92-321-1000001','parent@demo.com'),
(3,1,NULL,'ADM-003','Ahmed','Khan','male','2015-01-25',1,1,'general','Naveed Khan','+92-321-1000003','naveed@example.com'),
(4,1,NULL,'ADM-004','Ayesha','Noor','female','2015-09-14',1,2,'scholarship','Sana Noor','+92-321-1000004','sana@example.com'),
(5,1,NULL,'ADM-005','Hassan','Ali','male','2015-05-30',1,2,'general','Tariq Ali','+92-321-1000005','tariq@example.com'),
(6,1,NULL,'ADM-006','Zainab','Malik','female','2014-04-11',2,3,'general','Omar Malik','+92-321-1000006','omar@example.com'),
(7,1,NULL,'ADM-007','Usman','Sheikh','male','2014-08-19',2,3,'general','Faraz Sheikh','+92-321-1000007','faraz@example.com'),
(8,1,NULL,'ADM-008','Maryam',' Iqbal','female','2014-12-03',2,3,'sibling','Kiran Iqbal','+92-321-1000008','kiran@example.com'),
(9,1,NULL,'ADM-009','Bilal','Hussain','male','2013-06-22',3,4,'general','Asif Hussain','+92-321-1000009','asif@example.com'),
(10,1,NULL,'ADM-010','Hira','Shahid','female','2013-10-08',3,4,'general','Shahid Mehmood','+92-321-1000010','shahid@example.com');
INSERT INTO parent_student(tenant_id,parent_user_id,student_id) VALUES (1,7,1),(1,7,2);
INSERT INTO attendance(tenant_id,student_id,class_id,att_date,status,marked_by) VALUES
(1,1,1,CURDATE(),'present',4),(1,2,1,CURDATE(),'present',4),(1,3,1,CURDATE(),'present',4),(1,4,1,CURDATE(),'absent',4),(1,5,1,CURDATE(),'late',4),
(1,1,1,DATE_SUB(CURDATE(),INTERVAL 1 DAY),'present',4),(1,2,1,DATE_SUB(CURDATE(),INTERVAL 1 DAY),'present',4),(1,3,1,DATE_SUB(CURDATE(),INTERVAL 1 DAY),'late',4),(1,4,1,DATE_SUB(CURDATE(),INTERVAL 1 DAY),'present',4),(1,5,1,DATE_SUB(CURDATE(),INTERVAL 1 DAY),'present',4),
(1,6,2,CURDATE(),'present',4),(1,7,2,CURDATE(),'present',4),(1,8,2,CURDATE(),'absent',4);
INSERT INTO fee_structures(id,tenant_id,name,class_id,amount,frequency) VALUES (1,1,'Monthly Tuition — Grade 5',1,150,'monthly'),(2,1,'Monthly Tuition — Grade 6',2,180,'monthly'),(3,1,'Admission Fee',NULL,500,'one_time'),(4,1,'Transport Fee',NULL,50,'monthly');
INSERT INTO fee_invoices(id,tenant_id,student_id,structure_id,period,amount_due,amount_paid,status,due_date) VALUES
(1,1,1,1,DATE_FORMAT(NOW(),'%Y-%m'),150,150,'paid',DATE_ADD(CURDATE(),INTERVAL 10 DAY)),
(2,1,2,1,DATE_FORMAT(NOW(),'%Y-%m'),150,75,'partial',DATE_ADD(CURDATE(),INTERVAL 10 DAY)),
(3,1,3,1,DATE_FORMAT(NOW(),'%Y-%m'),150,0,'unpaid',DATE_ADD(CURDATE(),INTERVAL 10 DAY)),
(4,1,4,1,DATE_FORMAT(NOW(),'%Y-%m'),150,0,'unpaid',DATE_ADD(CURDATE(),INTERVAL 10 DAY)),
(5,1,5,1,DATE_FORMAT(NOW(),'%Y-%m'),150,150,'paid',DATE_ADD(CURDATE(),INTERVAL 10 DAY)),
(6,1,6,2,DATE_FORMAT(NOW(),'%Y-%m'),180,0,'unpaid',DATE_ADD(CURDATE(),INTERVAL 10 DAY));
INSERT INTO payments(tenant_id,subscription_id,amount,status,method,paid_at) VALUES (1,1,49,'paid','card',NOW());
INSERT INTO payments(tenant_id,student_id,fee_invoice_id,amount,status,method,paid_at) VALUES (1,1,1,150,'paid','cash',NOW()),(1,2,2,75,'paid','card',NOW()),(1,5,5,150,'paid','bank',NOW());
INSERT INTO invoices(tenant_id,payment_id,invoice_number,amount,status,issued_at) VALUES (1,1,'INV-2026-00001',49,'paid',NOW());
INSERT INTO exams(id,tenant_id,name,class_id,term,start_date,end_date,max_marks) VALUES (1,1,'Mid-Term Exam',1,'Term 1',CURDATE(),DATE_ADD(CURDATE(),INTERVAL 7 DAY),100);
INSERT INTO exam_marks(tenant_id,exam_id,student_id,subject_id,marks_obtained) VALUES
(1,1,1,1,92),(1,1,1,2,85),(1,1,1,3,88),
(1,1,2,1,78),(1,1,2,2,91),(1,1,2,3,74),
(1,1,3,1,65),(1,1,3,2,70),(1,1,3,3,69),
(1,1,4,1,88),(1,1,4,2,82),(1,1,4,3,90),
(1,1,5,1,55),(1,1,5,2,61),(1,1,5,3,58);
INSERT INTO timetable(tenant_id,class_id,subject_id,teacher_id,day_of_week,period_no,start_time,end_time,room) VALUES
(1,1,1,1,1,1,'08:00','08:45','R-1'),(1,1,2,3,1,2,'08:45','09:30','R-1'),(1,1,3,2,2,1,'08:00','08:45','Lab'),
(1,1,1,1,3,1,'08:00','08:45','R-1'),(1,1,2,3,4,2,'08:45','09:30','R-1'),(1,1,3,2,5,1,'08:00','08:45','Lab'),
(1,2,4,1,1,1,'08:00','08:45','R-2'),(1,2,5,3,2,2,'08:45','09:30','R-2');
INSERT INTO books(id,tenant_id,title,author,isbn,category,copies_total,copies_available) VALUES
(1,1,'Mathematics Grade 5','Oxford Press','978-019-001','Textbook',10,9),
(2,1,'English Reader 5','Cambridge','978-052-002','Textbook',8,8),
(3,1,'Science Explorers','National Book','978-969-003','Textbook',6,6),
(4,1,'The Hobbit','J.R.R. Tolkien','978-061-004','Fiction',3,3),
(5,1,'Pakistan Studies','A. Rahman','978-969-005','History',5,5);
INSERT INTO book_issues(tenant_id,book_id,student_id,issue_date,due_date,status) VALUES (1,1,2,CURDATE(),DATE_ADD(CURDATE(),INTERVAL 14 DAY),'issued');
INSERT INTO vehicles(id,tenant_id,vehicle_no,model,driver_name,driver_phone,capacity) VALUES (1,1,'BUS-101','Toyota Coaster','Muhammad Ramzan','+92-333-5550101',40);
INSERT INTO routes(id,tenant_id,name,vehicle_id,monthly_fee,stops) VALUES (1,1,'Route A — North Town',1,50,'North Town, Main Bazaar, School');
INSERT INTO transport_assignments(tenant_id,student_id,route_id,pickup_point) VALUES (1,1,1,'North Town Stop'),(1,3,1,'Main Bazaar');
INSERT INTO homework(tenant_id,class_id,subject_id,teacher_id,title,description,due_date) VALUES
(1,1,1,2,'Chapter 4 — Fractions exercises','Complete exercises 4A and 4B in your notebook.',DATE_ADD(CURDATE(),INTERVAL 2 DAY)),
(1,1,2,2,'Essay: My Favourite Season','Write a 200-word essay.',DATE_ADD(CURDATE(),INTERVAL 3 DAY));
INSERT INTO announcements(title,body) VALUES ('Welcome to EduPro','Your 14-day Standard trial is active.');
INSERT INTO support_tickets(tenant_id,user_id,subject,title,message,status) VALUES (1,2,'Need more student seats','Please advise on upgrading the student limit.','open');
INSERT INTO settings(tenant_id,setting_key,setting_value) VALUES (0,'platform_name','EduPro SaaS'),(0,'support_email','support@edupro.test'),(1,'currency','USD'),(1,'school_name','Demo School');
INSERT INTO activities(tenant_id,user_id,description) VALUES (1,2,'Demo school created with sample data'),(1,4,'Attendance marked for Grade 5');
INSERT INTO notifications(tenant_id,user_id,title,body) VALUES (1,2,'Welcome to EduPro','Your demo school is ready.');
