-- ISA ANSHORI, 2016 JANUARI 25, ADD COLUMN do_no
ALTER TABLE `erpdb`.`procurement_whbringbackh` 
ADD COLUMN  `do_no` char(50) DEFAULT '""' AFTER po_no;

-- ICHA MAILINDA, 2015 Desember 31, ADD Column deletion
ALTER TABLE `erpdb`.`procurement_whbringbackh` 
ADD COLUMN `deletion` VARCHAR(1000) DEFAULT '';

-- ICHA MAILINDA, 2015 Desember 15, ADD Column delete
ALTER TABLE imderpdb.procurement_whbringbackh
ADD COLUMN `deleted` tinyint(1) DEFAULT '0';