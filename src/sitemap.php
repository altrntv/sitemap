<?php
    namespace blitz\sitemap;

    use DOMDocument;
    use DOMException;

    class SiteMap
    {
        const xml = "xml";
        const csv = "csv";
        const json = "json";

        private array $array;
        private string $type;
        private string $save;

        public function CreateMap($array = [], $type = "", $save = ""): string
        {
            $this->array = $array;
            $this->type = $type;
            $this->save = $save;

            try
            {
                $this->DataValidity();
                $this->CreateDir();
                $this->WriteFile();
            } catch (MapException|DOMException $e)
            {
                die($e->getMessage());
            }

            return "Файл успешно создан";
        }


        // Создание директории
        /**
         * @throws FileCreateException
         */
        protected function CreateDir()
        {
            if (!file_exists($this->save))
            {
                $path = substr ($this->save, 0, strrpos($this->save, '/'));
                if (!is_dir( $path ))
                {
                    if (mkdir( $path, 0644, true) === false)
                    {
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
        protected function WriteFile()
        {
            switch ($this->type)
            {
                case self::xml:
                    $this->CreateXMLFile();
                    break;

                case self::csv:
                    $this->CreateCSVFile();
                    break;

                case self::json:
                    $this->CreateJSONFile();
                    break;
            }
        }

        /**
         * @throws DOMException
         * @throws FileWriteException
         */
        protected function CreateXMLFile(): bool
        {
            $dom = new DOMDocument('1.0', 'utf-8');
            $url_set = $dom->createElement('urlset');
            $url_set->setAttribute('xmlns:xsi','http://www.w3.org/2001/XMLSchema-instance');
            $url_set->setAttribute('xmlns','http://www.sitemaps.org/schemas/sitemap/0.9');
            $url_set->setAttribute('xsi:schemaLocation','http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd');
            $dom->appendChild($url_set);
            foreach ($this->array as $element)
            {
                $url = $dom->createElement("url");
                foreach ($element as $key => $value) {
                    $tag = $dom->createElement($key, $value);
                    $url->appendChild($tag);
                }
                $url_set->appendChild($url);
            }
            $result = $dom->save($this->save);

            if($result === false)
            {
                throw new FileWriteException();
            }

            return true;
        }

        /**
         * @throws FileWriteException
         */
        protected function CreateCSVFile(): bool
        {
            $fp = fopen($this->save, "c");

            if($fp === false)
            {
                throw new FileWriteException();
            }

            foreach ($this->array as $fields)
            {
                fputcsv($fp, $fields, ';');
            }

            fclose($fp);
            return true;
        }

        /**
         * @throws FileWriteException
         */
        protected function CreateJSONFile(): bool
        {
            $fp = fopen($this->save, "c");

            if($fp === false)
            {
                throw new FileWriteException();
            }

            fwrite($fp, json_encode($this->array, JSON_UNESCAPED_UNICODE));
            fclose($fp);

            return true;
        }

        //Проверка вводных данных


        /**
         * @throws TypeException
         * @throws ArrayException
         * @throws LocException
         */
        protected function DataValidity(): bool
        {
            $errors = [];

            foreach ($this->array as $key => $element) {
                if ( !is_string($element['loc']) || $element['loc'] == '' )
                {
                    $errors[$key]['loc'] = 'provide correct loc!';
                }

                if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $element['lastmod']))
                {
                    $errors[$key]['date'] = 'provide correct date!';
                }

                if ( !is_int($element['priority']) && !is_float($element['priority']) )
                {
                    $errors[$key]['priority'] = 'provide correct priority!';
                }

                if ( !is_string($element['changefreq']) || $element['changefreq'] == '' )
                {
                    $errors[$key]['changefreq'] = 'provide correct changefreq!';
                }
            }

            if($errors)
            {
                throw new ArrayException();
            }

            if($this->type != "xml" && $this->type != "csv" && $this->type != "json")
            {
                throw new TypeException();
            }

            if($this->save == "" || $this->save == "/")
            {
                throw new LocException();
            }

            return true;
        }
    }