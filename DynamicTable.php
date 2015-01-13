<?php

    /**
     * Permet de créer dynamiquement des bases de données et de les modifier
     */
    class DynamicTable {

        private $table;
        private $db;

        /**
         * Permet d'initialiser le module de table dynamique
         * @param string $table nom de la table
         * @param PDO $pdo instance d'une connexion mysql via PDO
         */
        public function __construct($table, $pdo) {
            $this->table = $this->replaceChars($table);
            $this->db = $pdo;
        }


        /**
         * Permet de créer la table
         * @param array $rows contenu de la table array('title' => titre, 'type' => type, 'comment' => commentaire)
         *
         * @return DynamicTable
         */
        public function create($rows) {
            if(!is_array($rows)) {
                throw new Exception("Vous devez rentrer les colonnes à créer sous forme d'un tableau.");
            }

            $create_sql = "CREATE TABLE IF NOT EXISTS `" . $this->table . "` ( `id` int(11) NOT NULL AUTO_INCREMENT, ";
            foreach($rows as $row) {
                if(!isset($row['title']) || !isset($row['type'])) {
                    throw new Exception("Vous devez spécifier le nom de la colonne et son type.");
                } else {
                    $comment = (isset($row['comment'])) ? " COMMENT '" . addslashes($row['comment']) . "'" : "";
                    $create_sql.= "`" . $this->replaceChars($row['title']) . "` " . $this->getRowType($row['type']) . " NOT NULL" . $comment . ",";
                }
            }
            $create_sql.= "PRIMARY KEY (`id`)) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1";

            try {
                $this->db->exec($create_sql);
                return new DynamicTable($this->table, $this->db);
            } catch (PDOException $e) {
                die($e->getMessage());
            }
        }


        /**
         * Permet de récupérer les colonnes de la table
         *
         * @return DynamicTable
         */
        public function getRows() {
            try {
                $recordset = $this->db->query("SHOW FULL COLUMNS FROM `" . $this->table . "`");
                return $recordset->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                die($e->getMessage());
            }
        }


        /**
         * Permet d'ajouter une colonne à la table
         * @param string $name nom de la colonne
         * @param string $type type de la colonne
         * @param string $order emplacement de la table (first, after ou end)
         * @param string $after après quelle colonne insérer la colonne (uniquement si after)
         * @param string $comment commentaire de la colonne
         *
         * @return DynamicTable
         */
        public function addRow($name, $type, $order = 'end', $after = null, $comment = null) {
            $comment = ($comment !== null) ? " COMMENT '" . addslashes($comment) . "'" : "";

            try {
                switch ($order) {
                    case 'first':
                        $this->db->exec("ALTER TABLE `" . $this->table . "` ADD `" . $this->replaceChars($name) . "` " . $this->getRowType($type) . " NOT NULL " . $comment . " FIRST");
                        break;

                    case 'after':
                        if(!$after !== null) {
                            throw new Exception("Vous devez indiquer après quelle colonne insérer " . $this->replaceChars($name));
                        } else {
                            $this->db->exec("ALTER TABLE `" . $this->table . "` ADD `" . $this->replaceChars($name) . "` " . $this->getRowType($type) . " NOT NULL " . $comment . " AFTER `" . $this->replaceChars($after) . "`");
                        }
                        break;

                    default:
                        $this->db->exec("ALTER TABLE `" . $this->table . "` ADD `" . $this->replaceChars($name) . "` " . $this->getRowType($type) . " NOT NULL " . $comment);
                        break;
                }

                return new DynamicTable($this->table, $this->db);
            } catch (PDOException $e) {
                die($e->getMessage());
            }
        }


        /**
         * Permet de renomer une colonne
         * @param string $oldName ancien nom de la table
         * @param string $newName nouveau nom de la table
         * @param string $type nouveau type de la table
         * @param string $comment commentaire de la colonne
         *
         * @return DynamicTable
         */
        public function renameRow($oldName, $newName, $type = null, $comment = null) {
            if($type !== null) {
                foreach($this->getRows() as $row) {
                    if($row['Field'] === $oldName) {
                        $type = $row['Type'];
                    }
                }
            }

            $comment = ($comment !== null) ? " COMMENT '" . addslashes($comment) . "'" : "";
            $this->db->exec("ALTER TABLE `" . $this->table . "` CHANGE `" . $this->replaceChars($oldName) . "` `" . $this->replaceChars($newName) . "` " . $this->getRowType($type) . " NOT NULL " . $comment);

            return new DynamicTable($this->table, $this->db);
        }


        /**
         * Permet de changer l'ordre des colonnes de la table
         * @param string $rowName nom de la colonne a deplacer
         * @param string $after nom de la colonne apres laquelle il faut deplacer $rowName
         *
         * @return DynamicTable
         */
        public function orderRow($rowName, $after) {
            foreach($this->getRows() as $row) {
                if($row['Field'] === $rowName) {
                    $type = $row['Type'];
                }
            }

            $this->db->exec("ALTER TABLE `" . $this->table . "` CHANGE `" . $this->replaceChars($rowName) . "` `" . $this->replaceChars($rowName) . "` " . $this->getRowType($type) . " NOT NULL AFTER `" . $this->replaceChars($after) . "`");

            return new DynamicTable($this->table, $this->db);
        }


        /**
         * Permet de supprimer une colonne
         * @param string $name nom de la colonne
         *
         * @return DynamicTable
         */
        public function deleteRow($name) {
            $this->db->exec("ALTER TABLE `" . $this->table . "` DROP `" . $this->replaceChars($name) . "`");

            return new DynamicTable($this->table, $this->db);
        }











        /**
         * Permet de renommer la table
         * @param string $newName nouveau nom de la table
         *
         * @return DynamicTable
         */
        public function renameTable($newName) {
            $this->db->exec("ALTER TABLE `" . $this->table . "` RENAME `" . $this->replaceChars($newName) . "`");
            $this->table = $this->replaceChars($newName);

            return new DynamicTable($this->table, $this->db);
        }


        /**
         * Permet de vider la table
         *
         * @return DynamicTable
         */
        public function truncate() {
            try {
                $this->db->exec("TRUNCATE `" . $this->table . "`");
                return new DynamicTable($this->table, $this->db);
            } catch (PDOException $e) {
                die($e->getMessage());
            }
        }


        /**
         * Permet de supprimer la table
         *
         * @return DynamicTable
         */
        public function drop() {
            try {
                $this->db->exec("DROP TABLE `" . $this->table . "`");
                return new DynamicTable($this->table, $this->db);
            } catch (PDOException $e) {
                die($e->getMessage());
            }
        }


        /**
         * Permet de recupérer le type à inscrire dans la requête SQL
         * @param string $type type de la colonne
         *
         * @return string type correct de la colonne
         */
        private function getRowType($type) {
            switch ($type) {
                case 'int' :
                case 'integer' :
                case 'int(11)':
                    return "int(11)";
                    break;

                case 'varchar':
                case 'varchar(255)':
                    return "varchar(255)";
                    break;

                case 'text':
                    return "text";
                    break;

                case 'date':
                    return "date";
                    break;

                default:
                    throw new Exception("Ce champs n'est pas encore prévu dans la classe.");
                    break;
            }
        }


        /**
         * Permet de prévenir les espaces et les accents dans le nom des colonnes
         * @params string $string nom de la colonne
         *
         * @return string le nom de la colonne sans les caractères spéciaux
         */
        private function replaceChars($string) {
            $in  = array('’',':',';',',',' ',"'",'"','&','~','(',')','{','#','[','|','`','\\','^','@',']','}','¤','%','§','/','À','Á','Â','Ã','Ä','Å','à','á','â','ã','ä','å','Ò','Ó','Ô','Õ','Ö','Ø','ò','ó','ô','õ','ö','ø','È','É','Ê','Ë','è','é','ê','ë','Ç','ç','Ì','Í','Î','Ï','ì','í','î','ï','Ù','Ú','Û','Ü','ù','ú','û','ü','ÿ','Ñ','ñ','À','Á','Â','Ã','Ä','Å','Ç','È','É','Ê','Ë','Ì','Í','Î','Ï','Ò','Ó','Ô','Õ','Ö','Ù','Ú','Û','Ü','Ý','à','á','â','ã','ä','å','ç','è','é','ê','ë','ì','í','î','ï','ð','ò','ó','ô','õ','ö','ù','ú','û','ü','ý','ÿ','+','–','.','€');
            $out = array('','','','','-','-','-','-','-','-','-','-','-','-','-','-','-','-','-','-','-','-','-','-','-','a','a','a','a','a','a','a','a','a','a','a','a','o','o','o','o','o','o','o','o','o','o','o','o','e','e','e','e','e','e','e','e','c','c','i','i','i','i','i','i','i','i','u','u','u','u','u','u','u','u','y','n','n','A','A','A','A','A','A','C','E','E','E','E','I','I','I','I','O','O','O','O','O','U','U','U','U','Y','a','a','a','a','a','a','c','e','e','e','e','i','i','i','i','o','o','o','o','o','o','u','u','u','u','y','y','-','-','','e');

            return strtolower(str_replace($in, $out, $string));
        }
    }
