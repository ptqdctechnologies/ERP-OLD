-- CAHYANING ANNISA, 31 Desember 2015, ADD Column Deletion
ALTER TABLE `erpdb`.`procurement_asfddcancel` 
ADD COLUMN `deletion` VARCHAR(1000) DEFAULT '';

-- CAHYANING ANNISA, 15 Desember 2015, ADD Column deleted
ALTER TABLE imderpdb.procurement_asfddcancel
ADD COLUMN `deleted` tinyint(1) DEFAULT '0';
