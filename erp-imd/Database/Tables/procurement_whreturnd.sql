-- ISA ANSHORI, 2016 JANUARI 25, ADD COLUMN do_no
ALTER TABLE `erpdb`.`procurement_whreturnd` 
ADD COLUMN  `do_no` char(50) DEFAULT '""' AFTER po_no;

-- ICHA MAILINDA, 2015 Desember 31, ADD Column deletion
ALTER TABLE `erpdb`.`procurement_whreturnd` 
ADD COLUMN `deletion` VARCHAR(1000) DEFAULT '';

-- ICHA MAILINDA, 2015 Desember 15, ADD Column approve dan delete
ALTER TABLE imderpdb.procurement_whreturnd
ADD COLUMN `approve` smallint(3) DEFAULT '100',
ADD COLUMN `deleted` tinyint(1) DEFAULT '0';