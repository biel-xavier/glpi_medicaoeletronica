-- Grant full permissions to Super-Admin profile (ID 4)
-- Rights: 1=READ, 2=UPDATE, 4=CREATE, 8=DELETE, 16=PURGE
-- 31 = READ + UPDATE + CREATE + DELETE + PURGE (full access)

INSERT INTO glpi_profilerights (profiles_id, name, rights) 
VALUES (4, 'medicaoeletronica', 31) 
ON DUPLICATE KEY UPDATE rights = 31;

-- You can also grant permissions to other profiles:
-- Profile ID 2 = Admin
-- INSERT INTO glpi_profilerights (profiles_id, name, rights) 
-- VALUES (2, 'medicaoeletronica', 3) 
-- ON DUPLICATE KEY UPDATE rights = 3;
-- (3 = READ + UPDATE)
