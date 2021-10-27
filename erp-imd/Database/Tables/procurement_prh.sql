-- ICHA MAILINDA, 2015 Desember 31, ADD Column deletion
ALTER TABLE `erpdb`.`procurement_prh` 
ADD COLUMN `deletion` VARCHAR(1000) DEFAULT '' ;

-- ICHA MAILINDA, 2015 Desember 15, ADD Column deleted
ALTER TABLE imderpdb.procurement_prh
ADD COLUMN `deleted` tinyint(1) DEFAULT '0';