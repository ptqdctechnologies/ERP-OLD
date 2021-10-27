-- ICHA MAILINDA, 2015 Desember 31, ADD Column deletion
ALTER TABLE `erpdb`.`procurement_pointd` 
ADD COLUMN `deletion` VARCHAR(1000) DEFAULT '';

-- ICHA MAILINDA, 2015 Desember 15, ADD Column approve dan deleted
ALTER TABLE imderpdb.procurement_pointd
ADD COLUMN `approve` smallint(3) DEFAULT '100',
ADD COLUMN `deleted` tinyint(1) DEFAULT '0';

-- ICHA, 2015 November 25, Add Column Date 
ALTER TABLE `erpdb`.`procurement_pointd` 
ADD COLUMN `date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `sts_internal`;

-- ISA, 2016 Januari 4, Add Column rateidr
ALTER TABLE `erpdb`.`procurement_pointd` 
ADD COLUMN `rateidr` decimal(12,4) DEFAULT '0.0000' AFTER harga;