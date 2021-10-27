-- ICHA MAILINDA, 2015 Desember 31, ADD Column deletion
ALTER TABLE `erpdb`.`procurement_whsupplierd` 
ADD COLUMN `deletion` VARCHAR(1000) DEFAULT '';

-- ICHA MAILINDA, 2015 Desember 15, ADD Column approve dan delete
ALTER TABLE imderpdb.procurement_whsupplierd
ADD COLUMN `approve` smallint(3) DEFAULT '100',
ADD COLUMN `deleted` tinyint(1) DEFAULT '0';