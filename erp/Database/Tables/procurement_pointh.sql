-- ICHA MAILINDA, 2015 Desember 31, ADD Column deletion
ALTER TABLE `erpdb`.`procurement_pointh` 
ADD COLUMN `deletion` VARCHAR(1000) DEFAULT '';

-- ICHA MAILINDA, 2015 Desember 15, ADD Column deleted
ALTER TABLE erpdb.procurement_pointh
ADD COLUMN `deleted` tinyint(1) DEFAULT '0';

-- ICHA, 2015 November 25, Add Column Date 
ALTER TABLE `erpdb`.`procurement_pointh` 
ADD COLUMN `date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `approve`;