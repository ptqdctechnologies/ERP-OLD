-- ISA ANSHORI, 15 Desember 2015, ADD Column deleted
ALTER TABLE imderpdb.accounting_petty_cash_in 
ADD COLUMN `deleted` tinyint(1) DEFAULT '0';

ALTER TABLE `erpdb`.`accounting_petty_cash_in` 
ADD COLUMN `deletion` VARCHAR(1000) DEFAULT '' ;




