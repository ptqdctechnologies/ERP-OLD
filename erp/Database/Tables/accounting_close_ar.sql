-- ISA ANSHORI, 15 Desember 2015, ADD Column deleted
ALTER TABLE erpdb.accounting_close_ar 
ADD COLUMN `deleted` tinyint(1) DEFAULT '0';

ALTER TABLE `erpdb`.`accounting_close_ar` 
ADD COLUMN `deletion` VARCHAR(1000) DEFAULT '' ;