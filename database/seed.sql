-- Seed data for development. Change the admin password immediately after first login.
SET NAMES utf8mb4;

-- Initial admin. Email: admin@ebeneza.org  Password: Ebeneza@2026!Admin  ← CHANGE THIS BEFORE PRODUCTION
INSERT INTO admins (name, email, password_hash) VALUES
('Ebeneza Super Admin','admin@ebeneza.org','$2y$10$gPxWYzMtetEnw8yQP/3TJOFNUnYOhxeCZZRv2Fus4iRfAo1zFSYCy');

INSERT INTO site_settings (setting_key, setting_value) VALUES
('org_name','Ebeneza Foundation'),
('org_registration','01NGO/R/9396'),
('org_address','P.O. Box 12597, Arusha, Tanzania'),
('org_email','ebenezafoundation2025@gmail.com'),
('org_whatsapp','+255 753 411 688'),
('bank_name','NBC Bank'),
('bank_account_name','Ebeneza Foundation'),
('bank_account_number','057172000891'),
('bank_swift','NLCBTZTX'),
('lipa_namba','4109234'),
('social_facebook',''),
('social_instagram',''),
('social_youtube',''),
('social_linkedin',''),
('social_x','');

-- Impact statistics are intentionally NOT seeded as verified.
-- Add verified figures via Admin -> Impact after attaching a source document.
