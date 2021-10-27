<?php

Class QDC_Finance_Jurnal {

    private $returnArray = true;
    private $json;
    private $defaultShape = array(
        "trano",
        "ref_number",
        "coa_kode",
        "coa_nama",
        "debit",
        "credit",
        "val_kode",
        "rateidr",
        "tgl",
        "uid",
        "memo"
    );
    private $jenisJurnal = array(
        "AP" => "Account Payable",
        "AR" => "Account Receivable",
        "bank" => "AP Jurnal Bank",
        "general_jurnal" => "General Journal",
        "bank_in" => "Bank Receive Money",
        "bank_out" => "Bank Spend Money",
        "petty_cash_in" => "Petty Cash Receive Money",
        "petty_cash_out" => "Petty Cash Spend Money",
        "inventory_in" => "Inventory In",
        "inventory_out" => "Inventory Out",
        "inventory_return" => "Inventory Return",
        "jurnal_settlement" => "Settlement Journal"
    );
    private $allModels = array(
        "AccountingCloseAP",
        "AccountingCloseAR",
        "AccountingInventoryIn",
        "AccountingInventoryOut",
        "AccountingInventoryReturn",
        "AccountingJurnalBank",
        "AccountingJurnalSettlement",
        "AdjustingJournal",
        "BankReceiveMoney",
        "BankSpendMoney",
        "PettyCashIn",
        "PettyCashOut",
    );

    public function __construct($params = '') {
        if ($params != '') {
            foreach ($params as $k => $v) {
                $temp = $k;
                $this->{"$temp"} = $v;
            }
        }

        if (!$this->json)
            $this->json = false;
    }

    public static function factory($params = array()) {
        return new self($params);
    }

    public function cekBalance() {
        if (!$this->jurnal)
            $this->showError("");

        $totDebit = 0;
        $totCredit = 0;
        foreach ($this->jurnal as $k => $v) {
            $totCredit += $v['credit'];
            $totDebit += $v['debit'];
        }

        if (bccomp($totCredit, $totDebit) != 0)
            $this->showError("Debit & Credit is not Balance yet!", $this->jurnal);
        else
            return true;
    }

    private function showError($msg, $data = '') {
        $err = array(
            "success" => false,
            "msg" => $msg,
            "data" => $data
        );
        if ($this->json) {
            echo Zend_Json::encode($err);
            exit(1); //TODOERP Bad ...
        } else
            return $err;
    }

    public function getAllJurnal($where = '',$order = '') {
        if (!$where)
            $where = null;

        if ($this->useTempTable) {
            $columns = array(
                array(
                    "column_name" => "id",
                    "type" => "INT"
                ),
                array(
                    "column_name" => "trano",
                    "type" => "VARCHAR(100)"
                ),
                array(
                    "column_name" => "ref_number",
                    "type" => "VARCHAR(255)"
                ),
                array(
                    "column_name" => "coa_kode",
                    "type" => "VARCHAR(100)"
                ),
                array(
                    "column_name" => "coa_nama",
                    "type" => "VARCHAR(255)"
                ),
                array(
                    "column_name" => "debit",
                    "type" => "DECIMAL(22,2)"
                ),
                array(
                    "column_name" => "credit",
                    "type" => "DECIMAL(22,2)"
                ),
                array(
                    "column_name" => "val_kode",
                    "type" => "VARCHAR(10)"
                ),
                array(
                    "column_name" => "rateidr",
                    "type" => "DECIMAL(22,2)"
                ),
                array(
                    "column_name" => "tgl",
                    "type" => "DATETIME"
                ),
                array(
                    "column_name" => "uid",
                    "type" => "VARCHAR(100)"
                ),
                array(
                    "column_name" => "memo",
                    "type" => "VARCHAR(100)"
                )
            );
        }

        $FINANCE = QDC_Model_Finance::init($this->allModels);

        $ap = $FINANCE->AccountingCloseAP->fetchAll($where.' AND deleted=0 ',$order);
        $origin = $FINANCE->AccountingCloseAP->__name();
        if ($ap) {
            $ap = $ap->toArray();
            if ($this->useShape) {
                $ap = $this->shapeJurnal($ap);

                if ($this->useTempTable) {
                    QDC_Adapter_TempTable::factory(array(
                        "tableName" => $this->tempTableName,
                        "add_data" => array(
                            "origin_table" => $origin
                        ),
                        "data" => $ap,
                        "cols" => $columns
                    ))->append();
                }
            }
        }

        $ar = $FINANCE->AccountingCloseAR->fetchAll($where.' AND deleted=0 ',$order);
        $origin = $FINANCE->AccountingCloseAR->__name();
        if ($ar) {
            $ar = $ar->toArray();
            if ($this->useShape) {
                $j = $this->shapeJurnal($ar);

                if ($this->useTempTable) {
                    QDC_Adapter_TempTable::factory(array(
                        "tableName" => $this->tempTableName,
                        "add_data" => array(
                            "origin_table" => $origin
                        ),
                        "data" => $j,
                        "cols" => $columns
                    ))->append();
                }
            }
        }

        $bank = $FINANCE->AccountingJurnalBank->fetchAll($where.' AND deleted=0 ',$order);
        $origin = $FINANCE->AccountingJurnalBank->__name();
        if ($bank) {
            $bank = $bank->toArray();
            if ($this->useShape) {
                $j = $this->shapeJurnal($bank);

                if ($this->useTempTable) {
                    QDC_Adapter_TempTable::factory(array(
                        "tableName" => $this->tempTableName,
                        "add_data" => array(
                            "origin_table" => $origin
                        ),
                        "data" => $j,
                        "cols" => $columns
                    ))->append();
                }
            }
        }

        $general = $FINANCE->AdjustingJournal->fetchAll($where.' AND deleted=0 ',$order);
        $origin = $FINANCE->AdjustingJournal->__name();
        if ($general) {
            $general = $general->toArray();
            if ($this->useShape) {
                $j = $this->shapeJurnal($general);

                if ($this->useTempTable) {
                    QDC_Adapter_TempTable::factory(array(
                        "tableName" => $this->tempTableName,
                        "add_data" => array(
                            "origin_table" => $origin
                        ),
                        "data" => $j,
                        "cols" => $columns
                    ))->append();
                }
            }
        }

        $bankIn = $FINANCE->BankReceiveMoney->fetchAll($where.' AND deleted=0 ',$order);
        $origin = $FINANCE->BankReceiveMoney->__name();
        if ($bankIn) {
            $bankIn = $bankIn->toArray();
            if ($this->useShape) {
                $j = $this->shapeJurnal($bankIn);

                if ($this->useTempTable) {
                    QDC_Adapter_TempTable::factory(array(
                        "tableName" => $this->tempTableName,
                        "add_data" => array(
                            "origin_table" => $origin
                        ),
                        "data" => $j,
                        "cols" => $columns
                    ))->append();
                }
            }
        }

        $bankOut = $FINANCE->BankSpendMoney->fetchAll($where.' AND deleted=0 ',$order);
        $origin = $FINANCE->BankSpendMoney->__name();
        if ($bankOut) {
            $bankOut = $bankOut->toArray();
            if ($this->useShape) {
                $j = $this->shapeJurnal($bankOut);

                if ($this->useTempTable) {
                    QDC_Adapter_TempTable::factory(array(
                        "tableName" => $this->tempTableName,
                        "add_data" => array(
                            "origin_table" => $origin
                        ),
                        "data" => $j,
                        "cols" => $columns
                    ))->append();
                }
            }
        }

        $pettyIn = $FINANCE->PettyCashIn->fetchAll($where.' AND deleted=0 ',$order);
        $origin = $FINANCE->PettyCashIn->__name();
        if ($pettyIn) {
            $pettyIn = $pettyIn->toArray();
            if ($this->useShape) {
                $j = $this->shapeJurnal($pettyIn);

                if ($this->useTempTable) {
                    QDC_Adapter_TempTable::factory(array(
                        "tableName" => $this->tempTableName,
                        "add_data" => array(
                            "origin_table" => $origin
                        ),
                        "data" => $j,
                        "cols" => $columns
                    ))->append();
                }
            }
        }

        $pettyOut = $FINANCE->PettyCashOut->fetchAll($where.' AND deleted=0 ',$order);
        $origin = $FINANCE->PettyCashOut->__name();
        if ($pettyOut) {
            $pettyOut = $pettyOut->toArray();
            if ($this->useShape) {
                $j = $this->shapeJurnal($pettyOut);

                if ($this->useTempTable) {
                    QDC_Adapter_TempTable::factory(array(
                        "tableName" => $this->tempTableName,
                        "add_data" => array(
                            "origin_table" => $origin
                        ),
                        "data" => $j,
                        "cols" => $columns
                    ))->append();
                }
            }
        }

        $invIn = $FINANCE->AccountingInventoryIn->fetchAll($where.' AND deleted=0 ',$order);
        $origin = $FINANCE->AccountingInventoryIn->__name();
        if ($invIn) {
            $invIn = $invIn->toArray();
            if ($this->useShape) {
                $j = $this->shapeJurnal($invIn);

                if ($this->useTempTable) {
                    QDC_Adapter_TempTable::factory(array(
                        "tableName" => $this->tempTableName,
                        "add_data" => array(
                            "origin_table" => $origin
                        ),
                        "data" => $j,
                        "cols" => $columns
                    ))->append();
                }
            }
        }

        $invOut = $FINANCE->AccountingInventoryOut->fetchAll($where.' AND deleted=0 ',$order);
        $origin = $FINANCE->AccountingInventoryOut->__name();
        if ($invOut) {
            $invOut = $invOut->toArray();
            if ($this->useShape) {
                $j = $this->shapeJurnal($invOut);

                if ($this->useTempTable) {
                    QDC_Adapter_TempTable::factory(array(
                        "tableName" => $this->tempTableName,
                        "add_data" => array(
                            "origin_table" => $origin
                        ),
                        "data" => $j,
                        "cols" => $columns
                    ))->append();
                }
            }
        }

        $invRet = $FINANCE->AccountingInventoryReturn->fetchAll($where.' AND deleted=0 ',$order);
        $origin = $FINANCE->AccountingInventoryReturn->__name();
        if ($invRet) {
            $invRet = $invRet->toArray();
            if ($this->useShape) {
                $j = $this->shapeJurnal($invRet);

                if ($this->useTempTable) {
                    QDC_Adapter_TempTable::factory(array(
                        "tableName" => $this->tempTableName,
                        "add_data" => array(
                            "origin_table" => $origin
                        ),
                        "data" => $j,
                        "cols" => $columns
                    ))->append();
                }
            }
        }

        /*$settle = $FINANCE->AccountingJurnalSettlement->fetchAll($where,$order);
        $origin = $FINANCE->AccountingJurnalSettlement->__name();
        if ($settle) {
            $settle = $settle->toArray();
            if ($this->useShape) {
                $j = $this->shapeJurnal($settle);

                if ($this->useTempTable) {
                    QDC_Adapter_TempTable::factory(array(
                        "tableName" => $this->tempTableName,
                        "add_data" => array(
                            "origin_table" => $origin
                        ),
                        "data" => $j,
                        "cols" => $columns
                    ))->append();
                }
            }
        }*/

        if ($this->returnArray) {
            $coaArray = QDC_Common_Array::factory()->merge(array($ap, $ar, $bank, $general, $bankIn, $bankOut, $pettyIn, $pettyOut, $invIn, $invOut, $invRet, $settle));

            $coaArray = $this->convertToIDR($coaArray);

            $totaldebit = 0;
            $totalcredit = 0;

            foreach ($coaArray as $k => $v) {
                if ($v['is_valueexchange'])
                    continue;

                if ($v['credit_conversion'] != 0)
                    $totalcredit += floatval($v['credit_conversion']);
                else
                    $totalcredit += floatval($v['credit']);

                if ($v['debit_conversion'] != 0)
                    $totaldebit += floatval($v['debit_conversion']);
                else
                    $totaldebit += floatval($v['debit']);
            }
            return array(
                "jurnal" => $coaArray,
                "count" => $count,
                "total_debit" => $totaldebit,
                "total_credit" => $totalcredit,
            );

//            return $coaArray;
        } else
            return true;
    }

    public function getAllJurnalWithStoreProcedure($where = '') {
        if (!$where)
            $where = null;
        else
            $where = " WHERE $where ";

        if ($this->useTempTable) {
            $columns = array(
                array(
                    "column_name" => "trano",
                    "type" => "VARCHAR(100)"
                ),
                array(
                    "column_name" => "ref_number",
                    "type" => "VARCHAR(255)"
                ),
                array(
                    "column_name" => "coa_kode",
                    "type" => "VARCHAR(100)"
                ),
                array(
                    "column_name" => "coa_nama",
                    "type" => "VARCHAR(255)"
                ),
                array(
                    "column_name" => "debit",
                    "type" => "DECIMAL(22,2)"
                ),
                array(
                    "column_name" => "credit",
                    "type" => "DECIMAL(22,2)"
                ),
                array(
                    "column_name" => "val_kode",
                    "type" => "VARCHAR(10)"
                ),
                array(
                    "column_name" => "rateidr",
                    "type" => "DECIMAL(22,2)"
                ),
                array(
                    "column_name" => "tgl",
                    "type" => "DATETIME"
                ),
                array(
                    "column_name" => "uid",
                    "type" => "VARCHAR(100)"
                ),
                array(
                    "column_name" => "memo",
                    "type" => "VARCHAR(100)"
                )
            );
        }

        $FINANCE = QDC_Model_Finance::init($this->allModels);
        $db = Zend_Registry::get('db');
        $origin = $FINANCE->AccountingCloseAP->__name();
        $query = $db->prepare("call get_all_journal(\"$where\",\"$origin\")");

        $query->execute();
        $ap = $query->fetchAll();
        $query->closeCursor();
        if ($ap) {
//            $ap = $ap->toArray();
            if ($this->useShape) {
                $ap = $this->shapeJurnal($ap);

                if ($this->useTempTable) {
                    QDC_Adapter_TempTable::factory(array(
                        "tableName" => $this->tempTableName,
                        "add_data" => array(
                            "origin_table" => $origin
                        ),
                        "data" => $ap,
                        "cols" => $columns
                    ))->append();
                }
            }
        }

        $origin = $FINANCE->AccountingCloseAR->__name();
        $query = $db->prepare("call get_all_journal(\"$where\",\"$origin\")");

        $query->execute();
        $ar = $query->fetchAll();
        $query->closeCursor();
        if ($ar) {
//            $ar = $ar->toArray();
            if ($this->useShape) {
                $j = $this->shapeJurnal($ar);

                if ($this->useTempTable) {
                    QDC_Adapter_TempTable::factory(array(
                        "tableName" => $this->tempTableName,
                        "add_data" => array(
                            "origin_table" => $origin
                        ),
                        "data" => $j,
                        "cols" => $columns
                    ))->append();
                }
            }
        }

        $origin = $FINANCE->AccountingJurnalBank->__name();
        $query = $db->prepare("call get_all_journal(\"$where\",\"$origin\")");

        $query->execute();
        $bank = $query->fetchAll();
        $query->closeCursor();
        if ($bank) {
//            $bank = $bank->toArray();
            if ($this->useShape) {
                $j = $this->shapeJurnal($bank);

                if ($this->useTempTable) {
                    QDC_Adapter_TempTable::factory(array(
                        "tableName" => $this->tempTableName,
                        "add_data" => array(
                            "origin_table" => $origin
                        ),
                        "data" => $j,
                        "cols" => $columns
                    ))->append();
                }
            }
        }


        $origin = $FINANCE->AdjustingJournal->__name();
        $query = $db->prepare("call get_all_journal(\"$where\",\"$origin\")");

        $query->execute();
        $general = $query->fetchAll();
        $query->closeCursor();
        if ($general) {
//            $general = $general->toArray();
            if ($this->useShape) {
                $j = $this->shapeJurnal($general);

                if ($this->useTempTable) {
                    QDC_Adapter_TempTable::factory(array(
                        "tableName" => $this->tempTableName,
                        "add_data" => array(
                            "origin_table" => $origin
                        ),
                        "data" => $j,
                        "cols" => $columns
                    ))->append();
                }
            }
        }


        $origin = $FINANCE->BankReceiveMoney->__name();
        $query = $db->prepare("call get_all_journal(\"$where\",\"$origin\")");

        $query->execute();
        $bankIn = $query->fetchAll();
        $query->closeCursor();
        if ($bankIn) {
//            $bankIn = $bankIn->toArray();
            if ($this->useShape) {
                $j = $this->shapeJurnal($bankIn);

                if ($this->useTempTable) {
                    QDC_Adapter_TempTable::factory(array(
                        "tableName" => $this->tempTableName,
                        "add_data" => array(
                            "origin_table" => $origin
                        ),
                        "data" => $j,
                        "cols" => $columns
                    ))->append();
                }
            }
        }


        $origin = $FINANCE->BankSpendMoney->__name();
        $query = $db->prepare("call get_all_journal(\"$where\",\"$origin\")");

        $query->execute();
        $bankOut = $query->fetchAll();
        $query->closeCursor();
        if ($bankOut) {
//            $bankOut = $bankOut->toArray();
            if ($this->useShape) {
                $j = $this->shapeJurnal($bankOut);

                if ($this->useTempTable) {
                    QDC_Adapter_TempTable::factory(array(
                        "tableName" => $this->tempTableName,
                        "add_data" => array(
                            "origin_table" => $origin
                        ),
                        "data" => $j,
                        "cols" => $columns
                    ))->append();
                }
            }
        }

        $origin = $FINANCE->PettyCashIn->__name();
        $query = $db->prepare("call get_all_journal(\"$where\",\"$origin\")");

        $query->execute();
        $pettyIn = $query->fetchAll();
        $query->closeCursor();
        if ($pettyIn) {
//            $pettyIn = $pettyIn->toArray();
            if ($this->useShape) {
                $j = $this->shapeJurnal($pettyIn);

                if ($this->useTempTable) {
                    QDC_Adapter_TempTable::factory(array(
                        "tableName" => $this->tempTableName,
                        "add_data" => array(
                            "origin_table" => $origin
                        ),
                        "data" => $j,
                        "cols" => $columns
                    ))->append();
                }
            }
        }


        $origin = $FINANCE->PettyCashOut->__name();
        $query = $db->prepare("call get_all_journal(\"$where\",\"$origin\")");

        $query->execute();
        $pettyOut = $query->fetchAll();
        $query->closeCursor();
        if ($pettyOut) {
//            $pettyOut = $pettyOut->toArray();
            if ($this->useShape) {
                $j = $this->shapeJurnal($pettyOut);

                if ($this->useTempTable) {
                    QDC_Adapter_TempTable::factory(array(
                        "tableName" => $this->tempTableName,
                        "add_data" => array(
                            "origin_table" => $origin
                        ),
                        "data" => $j,
                        "cols" => $columns
                    ))->append();
                }
            }
        }

        $origin = $FINANCE->AccountingInventoryIn->__name();
        $query = $db->prepare("call get_all_journal(\"$where\",\"$origin\")");

        $query->execute();
        $invIn = $query->fetchAll();
        $query->closeCursor();
        if ($invIn) {
//            $invIn = $invIn->toArray();
            if ($this->useShape) {
                $j = $this->shapeJurnal($invIn);

                if ($this->useTempTable) {
                    QDC_Adapter_TempTable::factory(array(
                        "tableName" => $this->tempTableName,
                        "add_data" => array(
                            "origin_table" => $origin
                        ),
                        "data" => $j,
                        "cols" => $columns
                    ))->append();
                }
            }
        }


        $origin = $FINANCE->AccountingInventoryOut->__name();
        $query = $db->prepare("call get_all_journal(\"$where\",\"$origin\")");

        $query->execute();
        $invOut = $query->fetchAll();
        $query->closeCursor();
        if ($invOut) {
//            $invOut = $invOut->toArray();
            if ($this->useShape) {
                $j = $this->shapeJurnal($invOut);

                if ($this->useTempTable) {
                    QDC_Adapter_TempTable::factory(array(
                        "tableName" => $this->tempTableName,
                        "add_data" => array(
                            "origin_table" => $origin
                        ),
                        "data" => $j,
                        "cols" => $columns
                    ))->append();
                }
            }
        }

        $origin = $FINANCE->AccountingInventoryReturn->__name();
        $query = $db->prepare("call get_all_journal(\"$where\",\"$origin\")");

        $query->execute();
        $invRet = $query->fetchAll();
        $query->closeCursor();
        if ($invRet) {
//            $invRet = $invRet->toArray();
            if ($this->useShape) {
                $j = $this->shapeJurnal($invRet);

                if ($this->useTempTable) {
                    QDC_Adapter_TempTable::factory(array(
                        "tableName" => $this->tempTableName,
                        "add_data" => array(
                            "origin_table" => $origin
                        ),
                        "data" => $j,
                        "cols" => $columns
                    ))->append();
                }
            }
        }

        $origin = $FINANCE->AccountingJurnalSettlement->__name();
        $query = $db->prepare("call get_all_journal(\"$where\",\"$origin\")");

        $query->execute();
        $settle = $query->fetchAll();
        $query->closeCursor();
        if ($settle) {
//            $settle = $settle->toArray();
            if ($this->useShape) {
                $j = $this->shapeJurnal($settle);

                if ($this->useTempTable) {
                    QDC_Adapter_TempTable::factory(array(
                        "tableName" => $this->tempTableName,
                        "add_data" => array(
                            "origin_table" => $origin
                        ),
                        "data" => $j,
                        "cols" => $columns
                    ))->append();
                }
            }
        }

        if ($this->returnArray) {
            $coaArray = QDC_Common_Array::factory()->merge(array($ap, $ar, $bank, $general, $bankIn, $bankOut, $pettyIn, $pettyOut, $invIn, $invOut, $invRet, $settle));
            $hasil = $this->convertToIDR($coaArray);

            $totaldebit = 0;
            $totalcredit = 0;

            foreach ($hasil as $k => $v) {
                if ($v['is_valueexchange'])
                    continue;

                if ($v['credit_conversion'] != 0)
                    $totalcredit += floatval($v['credit_conversion']);
                else
                    $totalcredit += floatval($v['credit']);

                if ($v['debit_conversion'] != 0)
                    $totaldebit += floatval($v['debit_conversion']);
                else
                    $totaldebit += floatval($v['debit']);
            }
            return array(
                "jurnal" => $hasil,
                "count" => $count,
                "total_debit" => $totaldebit,
                "total_credit" => $totalcredit,
            );
        } else
            return true;
    }

    public function getAllJurnalWithLimit($where = '', $offset = 0, $limit = 50, $order = 'tgl ASC', $groupBy = '', $arrayJurnal = array(), $bypassconvert = false) {
        if (!$where)
            $where = null;
        else
            $where = " WHERE $where AND deleted=0";

        if (!$order)
            $order_detail = null;
        else
            $order_detail = " Order by $order";

        $FINANCE = QDC_Model_Finance::init($this->allModels);
        $db = $FINANCE->db;

        $sql = "
            DROP TEMPORARY TABLE IF EXISTS `all_jurnal` ;
            CREATE TEMPORARY TABLE `all_jurnal` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `trano` varchar(255) NULL,
                `prj_kode` varchar(255) NULL,
                `sit_kode` varchar(255) NULL,
                `tgl` DATETIME NULL,
                `ref_number` varchar(255) NULL,
                `ref_number2` varchar(255) NULL,
                `coa_kode` varchar(255) NULL,
                `coa_nama` varchar(255) NULL,
                `debit` DECIMAL(22,2) NULL,
                `credit` DECIMAL(22,2) NULL,
                `job_number` VARCHAR(255) NULL,
                `jenis_jurnal` VARCHAR(50) NULL,
                `rateidr` DECIMAL(10,2) NULL,                
                `val_kode` VARCHAR(5) NULL,
                `memo` VARCHAR(100) NULL,
                `deleted` tinyint(1) DEFAULT '0',
                PRIMARY KEY (`id`)
            );
        ";
        $db->query($sql);
        $sql = "
            ALTER TABLE `all_jurnal`
            ADD INDEX `idx1`(`trano`),
            ADD INDEX `idx2`(`prj_kode`),
            ADD INDEX `idx3`(`sit_kode`),
            ADD INDEX `idx4`(`coa_kode`),
            ADD INDEX `idx5`(`ref_number`),
            ADD INDEX `idx6`(`tgl`),
            ADD INDEX `idx7`(`jenis_jurnal`),
            ADD INDEX `idx8`(`rateidr`),
            ADD INDEX `idx9`(`memo`);
        ";
        $sql = "
            INSERT INTO `all_jurnal` SELECT
                NULL,
                trano,
                prj_kode,
                sit_kode,
                date_format(tgl,'%Y-%m-%d') as tgl,
                ref_number,
                '',
                coa_kode,
                coa_nama,
                debit,
                credit,
                job_number,
                'AP',
                rateidr,
                val_kode,
                memo,
                deleted
            FROM
                {$FINANCE->AccountingCloseAP->__name()}
            $where $order_detail
            ;
        ";
        $db->query($sql);

        $sql = "
            INSERT INTO `all_jurnal` SELECT
                NULL,
                trano,
                prj_kode,
                sit_kode,
                date_format(tgl,'%Y-%m-%d') as tgl,
                ref_number,
                '',
                coa_kode,
                coa_nama,
                debit,
                credit,
                job_number,
                'AR',
                rateidr,
                val_kode,
                memo,
                deleted
            FROM
                {$FINANCE->AccountingCloseAR->__name()}
            $where $order_detail
            ;
        ";
        $db->query($sql);

        $sql = "
            INSERT INTO `all_jurnal` SELECT
                NULL,
                trano,
                prj_kode,
                sit_kode,
                date_format(tgl,'%Y-%m-%d') as tgl,
                ref_number,
                '',
                coa_kode,
                coa_nama,
                debit,
                credit,
                job_number,
                'bank',
                rateidr,
                val_kode,
                memo,
                deleted
            FROM
                {$FINANCE->AccountingJurnalBank->name()}
            $where $order_detail
            ;
        ";
        $db->query($sql);

        $sql = "
            INSERT INTO `all_jurnal` SELECT
                NULL,
                trano,
                prj_kode,
                sit_kode,
                date_format(tgl,'%Y-%m-%d') as tgl,
                ref_number,
                ref_number2,
                coa_kode,
                coa_nama,
                debit,
                credit,
                job_number,
                'general_jurnal',
                rateidr,
                val_kode,
                memo,
                deleted
            FROM
                {$FINANCE->AdjustingJournal->__name()}
            $where $order_detail
            ;
        ";
        $db->query($sql);

        $sql = "
            INSERT INTO `all_jurnal` SELECT
                NULL,
                trano,
                prj_kode,
                sit_kode,
                date_format(tgl,'%Y-%m-%d') as tgl,
                ref_number,
                '',
                coa_kode,
                coa_nama,
                debit,
                credit,
                job_number,
                'bank_in',
                rateidr,
                val_kode,
                '',
                deleted
            FROM
                {$FINANCE->BankReceiveMoney->__name()}
            $where $order_detail
            ;
        ";
        $db->query($sql);

        $sql = "
            INSERT INTO `all_jurnal` SELECT
                NULL,
                trano,
                prj_kode,
                sit_kode,
                date_format(tgl,'%Y-%m-%d') as tgl,
                ref_number,
                '',
                coa_kode,
                coa_nama,
                debit,
                credit,
                job_number,
                'bank_out',
                rateidr,
                val_kode,
                '',
                deleted
            FROM
                {$FINANCE->BankSpendMoney->__name()}
            $where $order_detail
            ;
        ";
        $db->query($sql);

        $sql = "
            INSERT INTO `all_jurnal` SELECT
                NULL,
                trano,
                prj_kode,
                sit_kode,
                date_format(tgl,'%Y-%m-%d') as tgl,
                ref_number,
                '',
                coa_kode,
                coa_nama,
                debit,
                credit,
                job_number,
                'petty_cash_in',
                0,
                val_kode,
                '',
                deleted
            FROM
                {$FINANCE->PettyCashIn->__name()}
            $where $order_detail
            ;
        ";
        $db->query($sql);


        $sql = "
            INSERT INTO `all_jurnal` SELECT
                NULL,
                trano,
                prj_kode,
                sit_kode,
                date_format(tgl,'%Y-%m-%d') as tgl,
                ref_number,
                '',
                coa_kode,
                coa_nama,
                debit,
                credit,
                job_number,
                'petty_cash_out',
                0,
                val_kode,
                '',
                deleted
            FROM
                {$FINANCE->PettyCashOut->__name()}
            $where $order_detail
            ;
        ";
        $db->query($sql);

        $sql = "
            INSERT INTO `all_jurnal` SELECT
                NULL,
                trano,
                prj_kode,
                sit_kode,
                date_format(tgl,'%Y-%m-%d') as tgl,
                ref_number,
                '',
                coa_kode,
                coa_nama,
                debit,
                credit,
                job_number,
                'inventory_in',
                rateidr,
                val_kode,
                '',
                deleted
            FROM
                {$FINANCE->AccountingInventoryIn->__name()}
            $where $order_detail
            ;
        ";
        $db->query($sql);
        $sql = "
            INSERT INTO `all_jurnal` SELECT
                NULL,
                trano,
                prj_kode,
                sit_kode,
                date_format(tgl,'%Y-%m-%d') as tgl,
                ref_number,
                '',
                coa_kode,
                coa_nama,
                debit,
                credit,
                job_number,
                'inventory_out',
                rateidr,
                val_kode,
                '',
                deleted
            FROM
                {$FINANCE->AccountingInventoryOut->__name()}
            $where $order_detail
            ;
        ";
        $db->query($sql);
        $sql = "
            INSERT INTO `all_jurnal` SELECT
                NULL,
                trano,
                prj_kode,
                sit_kode,
                date_format(tgl,'%Y-%m-%d') as tgl,
                ref_number,
                '',
                coa_kode,
                coa_nama,
                debit,
                credit,
                job_number,
                'inventory_return',
                rateidr,
                val_kode,
                '',
                deleted
            FROM
                {$FINANCE->AccountingInventoryReturn->__name()}
            $where $order_detail
            ;
        ";
        $db->query($sql);

        /*$sql = "
            INSERT INTO `all_jurnal` SELECT
                NULL,
                trano,
                prj_kode,
                sit_kode,
                date_format(tgl,'%Y-%m-%d') as tgl,
                ref_number,
                '',
                coa_kode,
                coa_nama,
                debit,
                credit,
                job_number,
                'jurnal_settlement',
                rateidr,
                val_kode,
                ''
            FROM
                {$FINANCE->AccountingJurnalSettlement->__name()}
            $where $order_detail
            ;
        ";
        $db->query($sql);*/



        if ($groupBy != '') {
            $group = "GROUP BY " . $groupBy;
        }

        if (count($arrayJurnal) > 0) {
            foreach ($arrayJurnal as $k => $v) {
                $arrayJurnal[$k] = "'" . $v . "'";
            }
            if ($where)
                $where .= " AND jenis_jurnal IN (" . implode(",", $arrayJurnal) . ")";
            else
                $where = " WHERE jenis_jurnal IN (" . implode(",", $arrayJurnal) . ")";
        }

       

        $sql = "
          SELECT SQL_CALC_FOUND_ROWS * FROM `all_jurnal` $where $group ORDER BY $order  LIMIT $offset,$limit ;
        ";

        $fetch = $db->prepare($sql);
        $fetch->execute();
        $hasil = $fetch->fetchAll();
        if (!$bypassconvert)
        $hasil = $this->convertToIDR($hasil);



        $count = $db->fetchOne("SELECT FOUND_ROWS()");
        $fetch->closeCursor();


        $sql = "
          SELECT SUM(debit) as total_debit, SUM(credit) as total_credit FROM `all_jurnal` $where $group ;
        ";

        $total = $db->fetchRow($sql);
        $totaldebit = 0;
        $totalcredit = 0;

        foreach ($hasil as $k => $v) {
            if ($v['is_valueexchange'])
                continue;

            if ($v['credit_conversion'] != 0)
                $totalcredit += floatval($v['credit_conversion']);
            else
                $totalcredit += floatval($v['credit']);

            if ($v['debit_conversion'] != 0)
                $totaldebit += floatval($v['debit_conversion']);
            else
                $totaldebit += floatval($v['debit']);
        }
        return array(
            "jurnal" => $hasil,
            "count" => $count,
            "total_debit" => $totaldebit,
            "total_credit" => $totalcredit,
        );
    }

    public function getAllJurnalTrialBalance($whereJurnal = array()) {
        if (!$whereJurnal)
            $whereJurnal = null;

        $where = null;
        $models = array(
            "AccountingCloseAP",
            "AccountingCloseAR",
            "AccountingJurnalBank",
            "AdjustingJournal",
            "BankReceiveMoney",
            "BankSpendMoney",
            "PettyCashIn",
            "PettyCashOut",
        );
        $FINANCE = QDC_Model_Finance::init($models);

        if ($whereJurnal['AP'] != '')
            $where = $whereJurnal['AP'];
        else
            $where = null;
        $ap = $FINANCE->AccountingCloseAP->fetchAll($where);
        if ($ap)
            $ap = $ap->toArray();

        if ($whereJurnal['AR'] != '')
            $where = $whereJurnal['AR'];
        else
            $where = null;
        $ar = $FINANCE->AccountingCloseAR->fetchAll($where);
        if ($ar)
            $ar = $ar->toArray();

        if ($whereJurnal['bank'] != '')
            $where = $whereJurnal['bank'];
        else
            $where = null;
        $bank = $FINANCE->AccountingJurnalBank->fetchAll($where);
        if ($bank)
            $bank = $bank->toArray();

        if ($whereJurnal['bank_non_bpv'] != '')
            $where = $whereJurnal['bank_non_bpv'];
        else
            $where = null;
        $bank_non_bpv = $FINANCE->AccountingJurnalBank->fetchAll($where);
        if ($bank_non_bpv)
            $bank_non_bpv = $bank_non_bpv->toArray();

        if ($whereJurnal['general'] != '')
            $where = $whereJurnal['general'];
        else
            $where = null;
        $general = $FINANCE->AdjustingJournal->fetchAll($where);
        if ($general)
            $general = $general->toArray();

        if ($whereJurnal['bank_in'] != '')
            $where = $whereJurnal['bank_in'];
        else
            $where = null;
        $bankIn = $FINANCE->BankReceiveMoney->fetchAll($where);
        if ($bankIn)
            $bankIn = $bankIn->toArray();

        if ($whereJurnal['bank_out'] != '')
            $where = $whereJurnal['bank_out'];
        else
            $where = null;
        $bankOut = $FINANCE->BankSpendMoney->fetchAll($where);
        if ($bankOut)
            $bankOut = $bankOut->toArray();

        if ($whereJurnal['petty_cash_in'] != '')
            $where = $whereJurnal['petty_cash_in'];
        else
            $where = null;
        $pettyIn = $FINANCE->PettyCashIn->fetchAll($where);
        if ($pettyIn)
            $pettyIn = $pettyIn->toArray();

        if ($whereJurnal['petty_cash_out'] != '')
            $where = $whereJurnal['petty_cash_out'];
        else
            $where = null;
        $pettyOut = $FINANCE->PettyCashOut->fetchAll($where);
        if ($pettyOut)
            $pettyOut = $pettyOut->toArray();

        $coaArray = QDC_Common_Array::factory()->merge(array($ap, $ar, $bank, $bank_non_bpv, $general, $bankIn, $bankOut, $pettyIn, $pettyOut));

        return $coaArray;
    }

    public function getInventoryJournal($where = '') {
        if (!$where)
            $where = null;

        $models = array(
            "AccountingInventoryIn",
            "AccountingInventoryOut",
            "AccountingInventoryReturn",
        );
        $FINANCE = QDC_Model_Finance::init($models);
        $db = $FINANCE->db;

        $sql = "
            DROP TEMPORARY TABLE IF EXISTS `inventory_jurnal` ;
            CREATE TEMPORARY TABLE `inventory_jurnal` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `trano` varchar(255) NULL,
                `tgl` DATETIME NULL,
                `ref_number` varchar(255) NULL,
                `coa_kode` varchar(255) NULL,
                `coa_nama` TEXT NULL,
                `debit` DECIMAL(22,2) NULL,
                `credit` DECIMAL(22,2) NULL,
                `jenis_jurnal` VARCHAR(50) NULL,
                PRIMARY KEY (`id`)
            );
        ";
        $db->query($sql);

        $sql = "
            ALTER TABLE `inventory_jurnal`
            ADD INDEX `idx1`(`trano`),
            ADD INDEX `idx2`(`coa_kode`),
            ADD INDEX `idx5`(`ref_number`),
            ADD INDEX `idx6`(`tgl`),
            ADD INDEX `idx7`(`jenis_jurnal`);
        ";
        $db->query($sql);

        $select = $db->select()
                ->from(array($FINANCE->AccountingInventoryIn->__name()), array(
            "id" => new Zend_Db_Expr("null"),
            "trano",
            "tgl",
            "ref_number",
            "coa_kode",
            "coa_nama",
            "debit" => "debit",
            "credit" => "credit",
            "jenis_jurnal" => new Zend_Db_Expr("'inventory_in'")
        ));

        $select = $select->group(array("trano", "ref_number", "coa_kode", "jenis_jurnal"));

        $sql = "INSERT INTO inventory_jurnal (" . (STRING) $select . ")";
        $db->query($sql);

        $select = $db->select()
                ->from(array($FINANCE->AccountingInventoryOut->__name()), array(
            "id" => new Zend_Db_Expr("null"),
            "trano",
            "tgl",
            "ref_number",
            "coa_kode",
            "coa_nama",
            "debit" => "debit",
            "credit" => "credit",
            "jenis_jurnal" => new Zend_Db_Expr("'inventory_out'")
        ));

        $select = $select->group(array("trano", "ref_number", "coa_kode", "jenis_jurnal"));
        $sql = "INSERT INTO inventory_jurnal (" . (STRING) $select . ")";
        $db->query($sql);

        $select = $db->select()
                ->from(array($FINANCE->AccountingInventoryReturn->__name()), array(
            "id" => new Zend_Db_Expr("null"),
            "trano",
            "tgl",
            "ref_number",
            "coa_kode",
            "coa_nama",
            "debit" => "debit",
            "credit" => "credit",
            "jenis_jurnal" => new Zend_Db_Expr("'inventory_return'")
        ));

        $select = $select->group(array("trano", "ref_number", "coa_kode", "jenis_jurnal"));
        $sql = "INSERT INTO inventory_jurnal (" . (STRING) $select . ")";
        $db->query($sql);

        $select = $db->select()
                ->from(array("inventory_jurnal"), array(
            new Zend_Db_Expr("SQL_CALC_FOUND_ROWS *")
        ));

        if ($where)
            $select = $select->where($where);

        $select = $select->order(array("tgl DESC"));


        $jurnal = $db->fetchAll($select);

        return $jurnal;
    }

    public function getArJournal($where = '', $type = '') {
        if (!$where)
            $where = null;

        $models = array(
            "AccountingCloseAR",
            "AccountingJurnalBank",
        );
        $FINANCE = QDC_Model_Finance::init($models);
        $db = $FINANCE->db;

        switch ($type) {
            case 'PAYMENT-AR-INV':
                $select = $db->select()
                        ->from(array($FINANCE->AccountingJurnalBank->__name()), array(
                    "id",
                    "trano",
                    "tgl",
                    "ref_number",
                    "coa_kode",
                    "coa_nama",
                    "debit" => "debit",
                    "credit" => "credit",
                    "type" => "type"
                ));
                break;
            default:
                $select = $db->select()
                        ->from(array($FINANCE->AccountingCloseAR->__name()), array(
                    "id",
                    "trano",
                    "tgl",
                    "ref_number",
                    "coa_kode",
                    "coa_nama",
                    "debit" => "debit",
                    "credit" => "credit",
                    "type" => "type"
                ));
                break;
        }

//        $select = $select->group(array("trano","ref_number","coa_kode","type"));

        if ($where)
            $select = $select->where($where);

        $select = $select->order(array("tgl DESC"));

        $jurnal = $db->fetchAll($select);

        return $jurnal;
    }

    public function getFixedAssetsJournal($where = '', $type = '') {
        if (!$where)
            $where = null;

        $models = array(
            "AccountingJurnalBank",
            "AccountingSaldoCoa",
            "MasterKategoriAsset"
        );
        $FINANCE = QDC_Model_Finance::init($models);


        $models = array(
            "MasterFixedAsset",
        );
        $LOGISTIC = QDC_Model_Logistic::init($models);
        $db = $FINANCE->db;

        if ($type == 'transaction') {

            $select = $db->select()
                    ->from(array("a" => $FINANCE->AccountingJurnalBank->name()), array(
                "a.id",
                "a.trano",
                "a.tgl",
                "a.ref_number",
                "a.coa_kode",
                "a.coa_nama",
                "a.debit as debit",
                "a.credit as credit",
                "a.type" => "type"
            ));
            $select = $select->where("a.type = 'FIXA'");
            $select = $select->joinLeft(array("b" => $LOGISTIC->MasterFixedAsset->name()), "a.trano = b.trano");
            $select = $select->order(array("a.tgl", "a.trano", "a.id"));
        } else {
            $select = $db->select()->from(array($FINANCE->MasterKategoriAsset->name()), array(
                        "b.coa_kode",
                        "b.coa_nama",
                        "b.totaldebit as debit",
                        "b.totalkredit as credit",
                        "b.val_kode",
                        "b.periode",
                        "b.tahun",
                        "b.total"
                    ))
                    ->joinLeft(array("b" => $FINANCE->AccountingSaldoCoa->name()), "coa_kode=coa_debit OR coa_kode=coa_credit", array())
                    ->group(array("coa_kode"));
        }

        if ($where)
            $select = $select->where($where);

        $jurnal = $db->fetchAll($select);


        return $jurnal;
    }

    public function shapeJurnal($jurnals = array()) {
        $tmp = array();
        $indeks = 0;
        foreach ($jurnals as $k => $v) {
            foreach ($this->defaultShape as $ind) {
                $tmp[$indeks][$ind] = $v[$ind];
            }
            $indeks++;
        }

        return $tmp;
    }

    public function convertToMasterCoa($jurnals = array()) {
        $mastercoa = new Finance_Models_MasterCoa();
        $cek = $masterCoa->fetchRow("coa_kode = '$coaKode'");

        $operand = '+';
        $totInsert = 0;
        if ($v['credit'] != '' && $v['credit'] > 0) {
            if ($cek['dk'] == 'Debit')
                $operand = '-';
            $totInsert = floatval($v['credit']);
        }
        else if ($v['debit'] != '' && $v['debit'] > 0) {
            if ($cek['dk'] == 'Credit')
                $operand = '-';
            $totInsert = floatval($v['debit']);
        }

        $newSaldo[$coaKode]['total'] = floatval($newSaldo[$coaKode]['total']) + (($operand == '-') ? -1 * floatval($totInsert) : $totInsert);
    }

    public function getAllModel() {
        return $this->allModels;
    }

    public function getJurnalType($type = '') {
        return $this->jenisJurnal[$type];
    }

    public function convertToIDR($array_data) {

        if (count($array_data) < 0)
            return false;

        //define coa n coa bank model object
        $coa_bank = new Finance_Models_MasterCoaBank();

        $arraytemp_debit = array();
        $arraytemp_credit = array();
        foreach ($array_data as $key => $val) {

            $exchange = true;
            $coa_nama = '';
            $trano = $val['trano'];
            $usd_bank = substr($val['trano'], 0, 3);
            $valuta_coabank_transaksi = $coa_bank->fetchRow("trano_type = '$usd_bank'");


            //find coa which is use for valuta exchange
            //filter coa in usd 
            if ($val['val_kode'] != 'IDR' && $val['val_kode'] != '') {
                $array_data[$key]['debit_conversion'] = 0;
                $array_data[$key]['credit_conversion'] = 0;
                $value = 0;

                $findExchange = strpos($val['coa_nama'], 'Exchange');
                $findGain = strpos($val['coa_nama'], 'Gain');
                $findLoss = strpos($val['coa_nama'], 'Loss');
                $memoExchange = strpos($val['memo'], 'Exchange');
                $memoGain = strpos($val['memo'], 'Gain');
                $memoLoss = strpos($val['memo'], 'Loss');

                 $ispbpv = strpos($val['trano'], 'BPV');

                $rateTotalminus = 0;
                $rateTotalplus = 0;
                foreach ($array_data as $k2 => $v2) {

                    if ($trano == $v2['trano']) {

                        // filter coa untuk menghilangkan coa2 hasil perkalian 
                        // nilai ori dengan exchange rate dikurangi nilai ori
                        if ($val['debit'] != 0) {

                            $rateTotalminus = round(($v2['debit'] * $v2['rateidr']) - $v2['debit'], 2);
                            $rateTotalplus = round(($v2['debit'] * $v2['rateidr']) + $v2['debit'], 2);
                            $rateTotal = round(($v2['debit'] * $v2['rateidr']), 2);

                            $value = round($val['debit'], 2);

                            if ($value == $rateTotalminus) {
                                $array_data[$key]['is_valueexchange'] = true;
                                $exchange = false;
                                $array_data[$k2]['is_ori'] = true;
                                $arraytemp_debit[$v2['trano']][$v2['coa_kode']][$val['job_number']]['has_exchange'] = true;
                                break;
                            } else if ($value == $rateTotalplus) {
                                $array_data[$key]['is_valueexchange'] = true;
                                $exchange = false;
                                $array_data[$k2]['is_ori'] = true;
                                $arraytemp_debit[$v2['trano']][$v2['coa_kode']][$val['job_number']]['has_exchange'] = true;
                                break;
                            } else if ($value == $rateTotal) {
                                $array_data[$key]['is_valueexchange'] = true;
                                $exchange = false;
                                $array_data[$k2]['exception_formula'] = true;
                                $array_data[$k2]['is_ori'] = true;
                                $arraytemp_debit[$v2['trano']][$v2['coa_kode']][$val['job_number']]['has_exchange'] = true;
                                break;
                            }
                        } else {
                            $rateTotalminus = round(($v2['credit'] * $v2['rateidr']) - $v2['credit'], 2);
                            $rateTotalplus = round(($v2['credit'] * $v2['rateidr']) + $v2['credit'], 2);
                            $rateTotal = round(($v2['credit'] * $v2['rateidr']), 2);

                            $value = round($val['credit'], 2);
                            if ($value == $rateTotalminus) {
                                $array_data[$key]['is_valueexchange'] = true;
                                $exchange = false;
                                $array_data[$k2]['is_ori'] = true;
                                $arraytemp_credit[$v2['trano']][$v2['coa_kode']][$val['job_number']]['has_exchange'] = true;
                                break;
                            } else if ($value == $rateTotalplus) {
                                $array_data[$key]['is_valueexchange'] = true;
                                $exchange = false;
                                $array_data[$k2]['is_ori'] = true;
                                $arraytemp_credit[$v2['trano']][$v2['coa_kode']][$val['job_number']]['has_exchange'] = true;
                                break;
                            } else if ($value == $rateTotal) {
                                $array_data[$key]['is_valueexchange'] = true;
                                $exchange = false;
                                $array_data[$k2]['exception_formula'] = true;
                                $array_data[$k2]['is_ori'] = true;
                                $arraytemp_credit[$v2['trano']][$v2['coa_kode']][$val['job_number']]['has_exchange'] = true;
                                break;
                            }
                        }
                    }
                }


                //coa2 exchange tidak dikalikan rate
                /*
                 *  ada beberapa kondisi exchange
                 *  1. coa bernilai exchange(IDR) dalam bentuk coa USD
                 *  2 .coa dalam usd, tetapi sebenarnya rupiah karena dia hasil gain/loss exchange rate
                 *  maka itu dia tidak dikalikan rate
                 *  3. memang coa itu coa untuk gain/loss exchange 
                 *  4. coa exchange yang terlihat dari memo-nya.
                 */
                if ($findExchange !== false) {
                    if ($findGain !== false || $findLoss !== false) {
                        $exchange = false;
                    } else {
                        if ($memoGain !== false || $memoLoss !== false) {
                            $exchange = false;
                        } else {
                            $array_data[$key]['is_valueexchange'] = true;
                            continue;
                        }
                    }
                }

                //coa2 exchange yang urut dengan coa asli
                if ($memoExchange !== false) {
                    if ($memoGain !== false || $memoLoss !== false) {
                        $exchange = false;
                    } else {
                        $array_data[$key]['is_valueexchange'] = true;
                        continue;
                    }
                }


                //jika coa dan bank untuk IDR tetapi digunakan untuk transaksi USD
                if (strpos($val['coa_nama'], 'IDR') !== false && $valuta_coabank_transaksi['val_kode'] == 'IDR')
                    $exchange = false;


                //bank digunakan oleh bpv
                if (strpos($val['coa_nama'], 'IDR') !== false && $val['val_kode'] != 'IDR' && $ispbpv !== false)
                    $exchange = false;

                //
                if (strpos($val['coa_nama'], 'IDR') !== false && $val['val_kode'] != 'IDR' && $valuta_coabank_transaksi == 'IDR') 
                    $exchange = false;

                if ($exchange) {
                    if ($val['debit'] != 0) {
                        $array_data[$key]['debit_conversion'] = floatval($val['debit'] * $val['rateidr']);
                    } else {
                        $array_data[$key]['credit_conversion'] = floatval($val['credit'] * $val['rateidr']);
                    }
                }
            }
        }


        //filter untuk menghitung total konversi
        foreach ($array_data as $k => $val) {

            //filter coa in usd 
            if ($val['val_kode'] != 'IDR' && $val['val_kode'] != '') {

                if ($val['debit_conversion'] > 0)
                    $arraytemp_debit[$val['trano']][$val['coa_kode']][$val['job_number']]['totaldebit_conversion'] += $val['debit_conversion'];

                if ($val['credit_conversion'] > 0)
                    $arraytemp_credit[$val['trano']][$val['coa_kode']][$val['job_number']]['totalcredit_conversion'] += $val['credit_conversion'];
            }
        }



        foreach ($array_data as $k => $v) {
            $bypass = true;
            $trano = $v['trano'];
            $coa = $v['coa_kode'];
            $job_number = $v['job_number'];
            $total_debit_convers = 0;
            $total_credit_convers = 0;
            //filter coa in usd
            if ($v['val_kode'] != 'IDR' && $v['val_kode'] != '') {

                $total_debit_convers = $arraytemp_debit["$trano"]["$coa"]["$job_number"]['totaldebit_conversion'];
                $total_credit_convers = $arraytemp_credit["$trano"]["$coa"]["$job_number"]['totalcredit_conversion'];

                if ($v['debit_conversion'] > 0)
                    if ($arraytemp_debit["$trano"]["$coa"]["$job_number"]["has_exchange"])
                        if ($v['debit_conversion'] > ($total_debit_convers - $v['debit_conversion']) && $v['debit_conversion'] != $total_debit_convers && !$v['is_ori'])
                            $bypass = false;

                if ($v['credit_conversion'] > 0)
                    if ($arraytemp_credit["$trano"]["$coa"]["$job_number"]["has_exchange"])
                        if ($v['credit_conversion'] > ($total_credit_convers - $v['credit_conversion']) && $v['credit_conversion'] != $total_credit_convers && !$v['is_ori'])
                            $bypass = false;

                if ($v['exception_formula'])
                    $bypass = true;


                if (!$bypass) {
                    if ($v['debit_conversion'] > 0) {
                        $array_data[$k]['debit_conversion'] = 0;
                        $array_data[$k]['is_valueexchange'] = true;
                    }
                    if ($v['credit_conversion'] > 0) {
                        $array_data[$k]['is_valueexchange'] = true;
                        $array_data[$k]['credit_conversion'] = 0;
                    }
                }
            }
        }

        //menghilangkan nilai2 exchange
        foreach ($array_data as $key => $val) {
            if ($val['is_valueexchange']) {
                unset($array_data[$key]);
            }
        }

        return $array_data;
    }

}

?>