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
            $this->table = $table;
            $this->db = $pdo;
        }


        /**
         * Permet de créer la table
         * @param array $rows contenu de la table (colonne => type)
         *
         * @return DynamicTable
         */
        public function create($rows) {
            if(!is_array($rows)) {
                die(htmlentities("Vous devez rentrer les colonnes à créer sous forme d'un tableau associatif."));
            }

            $create_sql = "CREATE TABLE IF NOT EXISTS `" . $this->table . "` ( `id` int(11) NOT NULL AUTO_INCREMENT,";
            foreach($rows as $row => $type) {
                $create_sql.= "`" . $row . "` " . $this->getRowType($type) . " NOT NULL,";
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
                $recordset = $this->db->query("SHOW COLUMNS FROM `" . $this->table . "`");
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
         *
         * @return DynamicTable
         */
        public function addRow($name, $type, $order = 'end', $after = null) {
            try {
                switch ($order) {
                    case 'first':
                        $this->db->exec("ALTER TABLE `" . $this->table . "` ADD `" . $name . "` " . $this->getRowType($type) . " NOT NULL FIRST");
                        break;

                    case 'after':
                        if(!$after !== null) {
                            die(htmlentities("Vous devez indiquer après quelle colonne insérer " . $name));
                        } else {
                            $this->db->exec("ALTER TABLE `" . $this->table . "` ADD `" . $name . "` " . $this->getRowType($type) . " NOT NULL AFTER `" . $after . "`");
                        }
                        break;

                    default:
                        $this->db->exec("ALTER TABLE `" . $this->table . "` ADD `" . $name . "` " . $this->getRowType($type) . " NOT NULL");
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
         *
         * @return DynamicTable
         */
        public function renameRow($oldName, $newName, $type = null) {
            if($type !== null) {
                foreach($this->getRows() as $row) {
                    if($row['Field'] === $oldName) {
                        $type = $row['Type'];
                    }
                }
            }

            $this->db->exec("ALTER TABLE `" . $this->table . "` CHANGE `" . $oldName . "` `" . $newName . "` " . $this->getRowType($type) . " NOT NULL");

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

            $this->db->exec("ALTER TABLE `" . $this->table . "` CHANGE `" . $rowName . "` `" . $rowName . "` " . $this->getRowType($type) . " NOT NULL AFTER `" . $after . "`");

            return new DynamicTable($this->table, $this->db);
        }


        /**
         * Permet de supprimer une colonne
         * @param string $name nom de la colonne
         *
         * @return DynamicTable
         */
        public function deleteRow($name) {
            $this->db->exec("ALTER TABLE `" . $this->table . "` DROP `" . $name . "`");

            return new DynamicTable($this->table, $this->db);
        }











        /**
         * Permet de renommer la table
         * @param string $newName nouveau nom de la table
         *
         * @return DynamicTable
         */
        public function renameTable($newName) {
            $this->db->exec("ALTER TABLE `" . $this->table . "` RENAME `" . $newName . "`");
            $this->table = $newName;

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
                    die(htmlentities("Ce champs n'est pas encore prévu dans la classe."));
                    break;
            }
        }
    }
