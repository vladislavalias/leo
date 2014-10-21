<?php

class simpleSender
{
  
  CONST CONFIG_READ_BLOCKS = 1;
  
  protected
    $name, $logFile, $logger, $outBuffer, $inBuffer, $stream, $splitResult = array();
  
  /**
   * @var array
   */
  protected $streamData;

  /**
   * @var array
   */
  protected $streamFiles;
  
  /**
   *
   * @var array
   */
  protected $headers = [];

  /**
   * @var array
   */
  public $meta;
    
  function __construct($logger = false)
  {
    $this->name     = 'simpleSenderClass';
    $this->logFile  = __DIR__.'/log/' .$this->name.'.log';
    $this->logger   = false;
    $this->logDebug = true;
    $this->logLvl   = 25;
    if ($logger) $this->setLogger($logger);
//    $this->getLogger()->logIt('SimpleSender started', 3);
    
    $this->streamTimeout  = 10;
    $this->streamPort     = 80;
    $this->streamMethod   = 'post';
    $this->streamData     = array();
    $this->streamFiles    = array();
  }
  
  function __destruct()
  {
//    $this->closeStream();
  }
  
  function _toString()
  {
    return 'Object '.$this->name.' to send : '.$this->getOutBuffer().'. receive : '.$this->getInBuffer();
  }
  
  /**
   * 
   * @param array $head
   * @return \simpleSender
   */
  public function setHeaders($head)
  {
    $this->headers = array_merge($this->headers, $head);
    
    return $this;
  }

  /**
   *
   * установка данных для отправки, принимается массив любого уровня вложенности
   * 
   * @param array $data - массив значений
   * @return array 
   */
  public function setData($data)
  {
    if (!$data) return false;
    
    $this->getLogger()->logIt('set data '.sizeof($data), 10);
    $this->streamData = array_merge($this->streamData, $data);
    
    return $this->streamData;
  }
  
  /**
   *
   * добавление файла для отправки постом
   * 
   * @param string $name - имя переменной файла при отправке
   * @param string $fileName  название файла
   * @param string $file - сам файл
   * @param string $type - тип файла, по-умолчанию application/octet-stream
   * @return array 
   */
  
  public function setFile($name, $fileName, $file, $type = 'application/octet-stream')
  {
    if (!$name || !$fileName || !$file) return false;
    
    $this->getLogger()->logIt('set file '.$name.' '.$fileName.' '.sizeof($file).' '.$type, 10);
    $this->streamFiles = array_merge($this->streamFiles, array($name => array('name' => $fileName, 'data' => $file, 'type' => $type)));
    
    return $this->streamFiles;
  }
  
  /**
   *
   * установка/считывание текущей настройки порта
   * 
   * @param type $port
   * @return type 
   */
  
  public function getPort($port = false)
  {
    if (!$port) return $this->streamPort;
    
    $this->getLogger()->logIt('set port '.$port, 12);
    $this->streamPort = $port;
    
    return $this->streamPort;
  }
  
  /**
   *
   * установка считывание текущей настрйоки таймаута
   * 
   * @param type $timeout
   * @return type 
   */
  
  public function getTimeout($timeout = false)
  {
    if (!$timeout) return $this->streamTimeout;
    
    $this->getLogger()->logIt('set timeout '.$timeout, 12);
    $this->streamTimeout = $timeout;
    
    return $this->streamTimeout;
  }
  
  /**
   *
   * установка считывание текущего метода отправки данных
   * 
   * @param type $method
   * @return type 
   */
  
  public function getMethod($method = false)
  {
    if (!$method) return $this->streamMethod;
    
    $this->getLogger()->logIt('set method '.$method, 12);
    $this->streamMethod = $method;
    
    return $this->streamMethod;
  }
  
  /**
   *
   * установка логгера
   * 
   * @param LogHistory $logger
   * @return type 
   */
  
  public function setLogger($logger)
  {
    if (!$logger || !($logger instanceof LogHistory)) return false;
    
    if (!$this->logger || !($this->logger instanceof LogHistory))
    {
      $this->logger = $logger;
    }
    
    return true;
  }
  
  /**
   *
   * отправка данныъ и считывание ответа сервера
   * 
   * @param string $url - если отправка по http протоколу то для POST его указывать не надо.
   * @param string $method - метод отправки данных
   * @param string $port - порт для подключения к серверу, относится только к посту
   * @param string $timeout - таймаут соединени, относится только к посту
   * @return string
   */
  
  public function sendData($url, $method = false, $port = false, $timeout = false)
  {
    if (!$url) return false;
    $this->getMethod($method);
    
    $this->clearBuffers();
    
    $this->getLogger()->logIt('send data to '.$url, 12);
    
    switch ($this->getMethod())
    {
      case 'get':
        $this->closeStream();
        $this->setGetStream($url);
        $this->readStream();
        break;
      case 'post':
        $this->closeStream();
        $this->getPort($port);
        $this->getTimeout($timeout);
        if (!$this->setSocket($url)) return false;
        $this->putStream($this->createPostPack($url));
        $this->readStream();
        break;
    }
    
    $this->splitResult($this->getInBuffer());
    $this->clearDatas();
    $this->doSomeActions($this->getHeadResult());
    
    return $this->getBodyResult();
  }
  
  /**
   * 
   * @param string $url
   * @param string $method
   * @param int $port
   * @param int $timeout
   * @return string
   */
  public function getRaw($url, $method = false, $port = false, $timeout = false)
  {
    $this->sendData($url, $method, $port, $timeout);
    
    return $this->getInBuffer();
  }


  /**
   *
   * получение входящего буфера данных
   * 
   * @return type 
   */
  
  public function getResult()
  {
    $this->getLogger()->logIt('get results', 15);
    return $this->getInBuffer();
  }
  
  public function getHeadResult()
  {
    if (!$this->splitResult || !array_key_exists('head', $this->splitResult)) $this->splitResult($this->getResult());
    
    return ($this->splitResult && $this->splitResult['head'])?$this->splitResult['head']:false;
  }
  
  public function getBodyResult()
  {
    if (!$this->splitResult || !array_key_exists('body', $this->splitResult)) $this->splitResult($this->getResult());
    
    return ($this->splitResult && $this->splitResult['body'])?$this->splitResult['body']:false;
  }
  
  /**
   *
   * формирование данных для отправки методом ГЕТ
   * 
   * @param array $data - массив для отправки любого уровня вложенности, для формирования используется рекурсия
   * @param string $name - название текущей переменной
   * @return string
   */
  
  function getDataGetMethod($data = false, $name = false)
  {
    if (!$this->streamData) return false;
    if (!$data) $data = $this->streamData;
    
    $this->getLogger()->logIt('create get data', 15);
    $varName = '';
    foreach ($data as $dataKey => $value)
    {
      if (!$name)
      {
        $varName = $dataKey;
      }
      else
      {
        $varName = $name.'['.$dataKey.']';
      }
      
      if (is_array($value))
      {
        $result[] = $this->getDataGetMethod($value, $varName);
        continue;
      }
      
      $result[] = $varName."=".  urlencode($value);
    }
    $result = implode('&', $result);
    
    return $result;
  }
  
  
  /**
   *
   * закрытие потока, данная функция стоит в деструкторе объекта
   * 
   * @return type 
   */
  
  function closeStream()
  {
    if (!$this->checkStream()) return false;
    
    $this->getLogger()->logIt('close stream', 15);
    try
    {
      fclose($this->stream);
    }
    catch (Exception $e)
    {
      $this->getLogger()->logIt('close failed', -1, 'ERROR');
      return false;
    }
    $this->stream = NULL;
    unset($this->stream);
    
    return true;
  }
  
  
  
  /**
   *
   * для разделения заголовка сообщения и тела сообщения
   * в дальнейшем можно добавить парсер заголовков, но пока смысла не вижу
   * 
   * @param type $result
   * @return type 
   */
  
  public function splitResult($result)
  {
    if (!$result) return false;
    $this->splitResult = array();
    
    $temp = preg_split("/\n[\r\n]+/", $result, 2, PREG_SPLIT_NO_EMPTY);
    if (sizeof($temp) != 1)
    {
      $this->splitResult['head'] = trim($temp[0]);
      $this->splitResult['body'] = trim($temp[1]);
    }
    else
    {
      $this->splitResult['head'] = '';
      $this->splitResult['body'] = $temp[0];
    }
    
    return $this->splitResult;
  }
  
  /**
   *
   * создание пакета ПОСТ данных
   * 
   * @param string $url - адрес для отправки данных
   * @return string 
   */
  
  protected function createPostPack($url)
  {
    if (!$url) return false;
    
    $this->getLogger()->logIt('create post message', 15);
    
    if (!$this->streamFiles)
    {
      $allBody = http_build_query($this->streamData);
      $boundary = '';
    }
    else
    {
      $body = $this->createPostBody($this->streamData, $boundary);
      $files = $this->createPostFiles($this->streamFiles, $boundary);
      $boundary = md5(rand(0, 1000).time());
      $allBody = rtrim($body.$files)."\r\n--".$boundary."--\r\n";
    }
    
    $head = $this->createPostHead($url, strlen($allBody), $boundary);
    
    $this->getLogger()->logIt($head.$allBody, 20);
    
    return $this->getOutBuffer($head.$allBody);
  }
  
  /**
   *
   * формирование заголовка пакета данных для отправки по ПОСТу
   * 
   * @param string $url - адрес для отправки данных, сервер и файл будут взяты из адреса
   * @param string $strlen - длина сообщения
   * @param string $boundary - разделитель между данными
   * @return string - заголовок данных
   */
  
  protected function createPostHead($url, $strlen, $boundary)
  {
    if (!$url) return false;
    
    $this->getLogger()->logIt('create post head', 15);
    $server = $this->getServerFromUrl($url, 2);
    $file = $this->getFileFromUrl($url, $server);
    
    $result = "POST ".$file." HTTP/1.1\r\n";
    $result .= "User-Agent: Opera/9.80 (X11; Linux i686) Presto/2.12.388 Version/12.16\r\n";
    $result .= "Host: ".$server."\r\n";
    $result .= "Accept: text/html, application/xml;q=0.9, application/xhtml+xml, image/png, image/webp, image/jpeg, image/gif, image/x-xbitmap, */*;q=0.1\r\n";
    $result .= "Accept-Language: ru-RU,ru;q=0.9,en;q=0.8\r\n";
    $result .= "Referer: http://lingualeo.com/ru/\r\n";
    $result .= "Connection: Keep-Alive\r\n";
    $result .= "DNT: 1\r\n";
    if ($boundary)
    {
      $result .= "Content-type: multipart/form-data, boundary=".$boundary."\r\n";
    }
    else
    {
      $result .= "Content-Type: application/x-www-form-urlencoded\r\n";
    }
    
    $result .= $this->headers ? implode("\r\n", $this->headers) : '';
    $result .= "Content-length: ".$strlen."\r\n\r\n";
    
    return $result;
  }
  
  /**
   *
   * присоединение указанных файлов
   * 
   * @param array $files - массив файлов
   * @param string $boundary - разделитель
   * @return string - часть пакета с файлами
   */
  
  protected function createPostFiles($files, $boundary)
  {
    if (!$files || !$boundary) return false;
    
    $this->getLogger()->logIt('create post files', 15);
    $result = '';
    foreach ($files as $dataKey => $value)
    {
      $result .= $boundary."\r\n";
      $result .= "Content-Disposition: form-data; name=\"".$dataKey."\"; filename=\"".$value['name']."\"\r\n";
      $result .= "Content-Type: ".$value['type']."\r\n\r\n";
      $result .= $value['data']."\r\n";
    }
    
    return $result;
  }
  
  /**
   *
   * формирование части пакета с данными для отправки по посту
   * 
   * @param array $data - данные для отправки - массив любого уровня вложенности, для правильного построения данных иуспользуется рекурсия
   * @param string $boundary - разделитель
   * @param string $name - текущее имя переменной, для рекурсивного обхода
   * @return string 
   */
  
  protected function createPostBody($data, $boundary, $name = '')
  {
    if (!$data || !$boundary) return false;
    
    $this->getLogger()->logIt('create post body '.$name, 15);
    $result = '';
    $varName = '';
    foreach ($data as $dataKey => $value)
    {
      if (!$name)
      {
        $varName = $dataKey;
      }
      else
      {
        $varName = $name.'['.$dataKey.']';
      }
      
      if (is_array($value))
      {
        $result .= $this->createPostBody($value, $boundary, $varName);
        continue;
      }
      
      $result .= '--'.$boundary."\r\n";
      $result .= "Content-Disposition: form-data; name=\"".$varName."\"\r\n\r\n";
      $result .= $value."\r\n";
    }
    
    return $result;
  }
  
  /**
   *
   * проверка уже открытого потока
   * 
   * @return type 
   */
  
  protected function checkStream()
  {
    if (isset($this->stream) && $this->stream) return true;
    
    return false;
  }
  
  /**
   *
   * создание ГЕТ потока
   * 
   * @param string $url - адрес для отправки данных, данные будут сформированы автоматически из ввееденных ранее методом setData
   * @return type 
   */
  
  protected function setGetStream($url)
  {
    if (!$url) return false;
    
    $this->getLogger()->logIt('set GET stream', 15);
    $datas = $this->getDataGetMethod();
    $this->getLogger()->logIt('put in GET stream '.$datas, 20);
    $this->stream = fopen($url.($datas?'?'.$datas:''), "r");
    
    $this->meta = stream_get_meta_data($this->stream);
    
    return $this->stream ? true : false;
  }
  
  /**
   *
   * создание сокета для отправки данных по посту
   * 
   * @param string $url - адрес отправки, сервер и файл назначения будут определены автоматически, для http протокола указывать http не надо.
   * @return type 
   */
  
  protected function setSocket($url)
  {
    if ($this->checkStream()) return true;
    
    $this->getLogger()->logIt('set POST stream', 15);
    try
    {
      $this->stream = stream_socket_client($this->getServerFromUrl($url).($this->getPort()?':'.$this->getPort():''), $errno, $errstr, $this->getTimeout());
    }
    catch (Exception $e)
    {
      $this->getLogger()->logIt($errno.' & '.$errstr, -1, 'ERROR');
      return false;
    }
    
    $this->getLogger()->logIt('creating stream '.($this->checkStream()?'success':'failed').' '.$url.' '.$this->getPort().' '.$this->getTimeout(), 15);
    
    return $this->checkStream();
  }
  
  /**
   *
   * чтение данных из текущего открытого потока, формат для ГЕТ потока и ПОСТ потока совпадают
   * 
   * @return type 
   */
  
  protected function readStream()
  {
    if (!$this->checkStream()) return false;
    
    $preRead = $this->getInBuffer(fread($this->stream, self::CONFIG_READ_BLOCKS));
    
    if (strpos($this->getInBuffer(), 'Transfer-Encoding: chunked') !== false)
    {
      $this->readChunkedStream();
    }
    $this->readSimpleStream();
    
    return $this->getInBuffer();
  }
  
  protected function readSimpleStream()
  {
    if (!$this->checkStream()) return false;
    
    while (!feof($this->stream))
    {
      $this->getInBuffer(fread($this->stream, self::CONFIG_READ_BLOCKS));
    }
    
    return true;
  }

  protected function readChunkedStream()
  {
    if (!$this->checkStream()) return false;
    
    while (!$this->checkReadingEnd($this->getInBuffer()))
    {
      $this->getInBuffer(fread($this->stream, self::CONFIG_READ_BLOCKS));
    }
    
    return true;
  }
  
  /**
   *
   * запись данных в открытый ранее ПОСТ поток
   * 
   * @param string $data - данные для отправки, строка.
   * @return type 
   */
  
  protected function putStream($data)
  {
    if (!$this->checkStream() || !$data) return false;
    
    dump($data, false);
    
    $this->getLogger()->logIt('put in stream '.substr($data, 0, 20), 13);
//    stream_set_blocking($this->stream, 0);
    fwrite($this->stream, $data);
    
    return true;
  }
  
  /**
   *
   * очистка входящих/исодящих буферов перед отправкой новых данных
   * 
   * @return type 
   */
  
  protected function clearBuffers()
  {
    $this->getLogger()->logIt('clear buffers', 15);
    $this->inBuffer     = '';
    $this->outBuffer    = '';
    $this->splitResult  = '';
    
    return true;
  }
  
  
  protected function clearDatas()
  {
    $this->streamData = array();
    $this->streamFiles = array();
    
    return true;
  }
  
  /**
   *
   * получение буффера полученных данных
   * 
   * @param type $string
   * @return type 
   */
  
  protected function getInBuffer($string = false)
  {
    if ($string) $this->inBuffer .= $string;
    
    return $this->inBuffer;
  }
  
  /**
   *
   * получение внутреннего буффера на отправку
   * 
   * @param type $string
   * @return type 
   */
  
  protected function getOutBuffer($string = false)
  {
    if ($string) $this->outBuffer .= $string;
    
    return $this->outBuffer;
  }
  
  /**
   *
   * получение логгера
   * 
   * @return type 
   */
  
  protected function getLogger()
  {
    if ($this->logger && ($this->logger instanceof LogHistory)) return $this->logger;
    
    $this->logger = new LogHistory($this->logFile, $this->logDebug, $this->logLvl);
    
    return $this->logger;
  }
  
  /**
   *
   * получение файла назначения из указанного ранее адреса, если файла нет то будет просто пустота
   * 
   * @param type $url
   * @return type 
   */
  
  protected function getFileFromUrl($url, $server)
  {
    if (!$url) return false;
 
    $file = substr($url, (strpos($url, $server) + strlen($server)));
    if (!$file) $file = 'index.php';
    $this->getLogger()->logIt('get file '.$file, 5);
    
    return $file;  
  }
  
  /**
   *
   * получение адреса сервера для отправки из укзанного ранее адреса, возвращается все, включая протокол, до первого слеша.
   * 
   * @param string $url
   * @return type 
   */
  
  protected function getServerFromUrl($url, $position = 0)
  {
    if (!$url) return false;
 
    preg_match('/([\w\.]*:\/\/)?([^\/]*)/', $url, $out);
    $this->getLogger()->logIt('get server '.$out[0], 5);
    
    return $out[$position];  
  }
  
  protected function checkReadingEnd($data, $inc = false)
  { 
    if (!$data) return false;
    
    if (strpos($data, "\r\n0\r\n\r\n") === false)
    {
      return false;
    }
    
    return true;
  }
  
  protected function doSomeActions($head)
  {
    if ($head) return false;
    
    if (strpos($head, 'Connection: close') !== false)
    {
      $this->closeStream();
    }
    
    return true;
  }
}