-- ISA ANSHORI, 15 Desember 2015, ADD Column deleted
ALTER TABLE imderpdb.accounting_jurnal_bank
ADD COLUMN `deleted` tinyint(1) DEFAULT '0';

ALTER TABLE `erpdb`.`accounting_jurnal_bank` 
ADD COLUMN `deletion` VARCHAR(1000) DEFAULT '' ;
