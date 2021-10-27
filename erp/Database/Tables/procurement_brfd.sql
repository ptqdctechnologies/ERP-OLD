-- CAHYANING ANNISA, 31 Desember 2015, ADD Column Deletion
ALTER TABLE `erpdb`.`procurement_brfd` 
ADD COLUMN `deletion` VARCHAR(1000) DEFAULT '';

-- ISA ANSHORI, 15 Desember 2015, ADD Column deleted
ALTER TABLE erpdb.procurement_brfd 
ADD COLUMN `deleted` tinyint(1) DEFAULT '0';



