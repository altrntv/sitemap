<?php
    namespace blitz\sitemap;

    use DOMDocument;
    use DOMException;
    use Exception;

    class sitemap {
        private array $array;
        private mixed $type;
        private mixed $save;

        public function __construct($array = [], $type = "", $save = "") {
            $this->array = $array;
            $this->type = $type;
            $this->save = $save;
        }

        public function create_map(): string
        {
            try {
                $this->dataValidity();
                $this->createDir();
                $this->writeFile();
            } catch (mapException|DOMException $e) {
                die($e->getMessage());
            }

            return "Файл успешно создан";
        }


        // Создание директории
        /**
         * @throws FileCreateException
         */
        protected function createDir() {
            if (!file_exists($this->save)){
                if (!is_dir( substr ($this->save, 0, strrpos($this->save, '/')) )) {
                    if (mkdir(substr ($this->save, 0, strrpos($this->save, '/')), 0777, true) === false)  {
                        throw new FileCreateException();
                    }

                    return true;
                }
            }
        }

        // Создание и запись файла
        /**
         * @throws FileWriteException
         * @throws DOMException
         */
        protected function writeFile() {
            switch ($this->type) {
                case "xml":
                    $dom = new DOMDocument('1.0', 'utf-8');
                    $url_set = $dom->createElement('urlset');
                    $url_set->setAttribute('xmlns:xsi','http://www.w3.org/2001/XMLSchema-instance');
                    $url_set->setAttribute('xmlns','http://www.sitemaps.org/schemas/sitemap/0.9');
                    $url_set->setAttribute('xsi:schemaLocation','http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd');
                    $dom->appendChild($url_set);
                    foreach ($this->array as $element) {
                        $url = $dom->createElement("url");
                        foreach ($element as $key => $value) {
                            $tag = $dom->createElement($key, $value);
                            $url->appendChild($tag);
                        }
                        $url_set->appendChild($url);
                    }
                    $result = $dom->save($this->save);

                    if($result === false) {
                        throw new FileWriteException();
                    }

                    return true;

                case "csv":
                    $fp = fopen($this->save, "c");

                    if($fp === false) {
                        throw new FileWriteException();
                    }

                    foreach ($this->array as $fields) {
                        fputcsv($fp, $fields, ';');
                    }

                    fclose($fp);
                    return true;

                case "json":
                    $fp = fopen($this->save, "c");

                    if($fp === false) {
                        throw new FileWriteException();
                    }

                    fwrite($fp, json_encode($this->array, JSON_UNESCAPED_UNICODE));
                    fclose($fp);

                    return true;
            }
        }

        //Проверка вводных данных

        /**
         * @throws typeException
         * @throws arrayException
         * @throws locException
         */
        protected function dataValidity(): bool
        {
            $errors = [];

            foreach ($this->array as $key => $element) {
                if (!is_string($element['loc']) ||
                    $element['loc'] == ''
                )
                    $errors[$key]['loc'] = 'provide correct loc!';

                if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $element['lastmod']))
                    $errors[$key]['date'] = 'provide correct date!';

                if ( !is_int($element['priority']) &&
                    !is_float($element['priority'])
                )
                    $errors[$key]['priority'] = 'provide correct priority!';

                if (!is_string($element['changefreq']) ||
                    $element['changefreq'] == ''
                )
                    $errors[$key]['changefreq'] = 'provide correct changefreq!';
            }

            if($errors) {
                throw new arrayException();
            }

            if($this->type != "xml" && $this->type != "csv" && $this->type != "json") {
                throw new typeException();
            }

            if($this->save == "" || $this->save == "/") {
                throw new locException();
            }

            return true;
        }
    }


    // Исключения
    class mapException extends Exception {

    }

    class FileCreateException extends mapException {
        protected $message = "Ошибка создания директории!";
    }

    class FileWriteException extends mapException {
        protected $message = "Ошибка записи в файл";
    }

    class arrayException extends mapException {
        protected $message = "Невалидные данные в массиве со списоком страниц сайта";
    }

    class typeException extends mapException {
        protected $message = "Неверно указан тип файла";
    }

    class locException extends mapException {
        protected $message = "Неверно указан путь сохранения файла";
    }