-- CAHYANING ANNISA, 31 Desember 2015, ADD Column Deletion
ALTER TABLE `erpdb`.`transengineer_afed` 
ADD COLUMN `deletion` VARCHAR(1000) DEFAULT '';

-- CAHYANING ANNISA, 15 Desember 2015, ADD Column approve dan deleted
ALTER TABLE imderpdb.transengineer_afed
ADD COLUMN `approve` smallint(3) DEFAULT '100',
ADD COLUMN `deleted` tinyint(1) DEFAULT '0';