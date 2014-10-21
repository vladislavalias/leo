<?php
class LogHistory
{
  private $fileStrms;

  function __toString()
  {
    return $this->logFile.' now logged. filestreams ='.sizeof($this->filesStrms);
  }
  
  function __construct($logFileName, $debug = true, $logLvl = 15)
  {
    if (!$logFileName) return false;
    $this->logFile     = $logFileName;
    $this->debugIS     = $debug;
    $this->logLvl      = $logLvl;
    $this->filesStrms  = array();
    $this->start();
  }
  
  function __destruct()
  {
    $this->finish();
  }
  
  /**
   * 
   * функция для проведения начала отсчета для оценки времени работы скрипта
   * 
   */
  
  function startTime()
  {
    //Считываем текущее время 
    $mtime = microtime(); 
    //Разделяем секунды и миллисекунды 
    $mtime = explode(" ",$mtime); 
    //Составляем одно число из секунд и миллисекунд 
    $this->tstart = $mtime[1] + $mtime[0];
  }
  
  /**
   *
   * вычисление разницы времени между временем начала работы скрипта и текущим временем
   * 
   * @return type 
   */
  
  protected function getDiffStartTime()
  {
    $mtime = microtime(); 
    $mtime = explode(" ",$mtime); 
    $tend = $mtime[1] + $mtime[0];
    //Вычисляем разницу 
    if (!@$this->tstart) $this->tstart = $tend;
    $totaltime = ($tend - @$this->tstart); 
    return $totaltime;
  }
  
  /**
   * 
   * функция для проведения окочания и вывода времени работы скрипта
   * 
   */
  
  function finishTime()
  {    
    //Делаем все то же самое, чтобы получить текущее время 
    $totaltime = $this->getDiffStartTime();
    //Выводим не экран 
    return "Task finished for: ".$totaltime." s.\n";
  }
  
  
  /**
   * 
   * функция вывода задействованной оперативки
   * 
   */
  
  function showMemory()
  {
    return 'Used memory: '.round((memory_get_usage()/1024), 2)." кб";    
  }
  
  /**
   *
   * функция для логирования событий работы скрипта, соответственно разрешенной детализации логирования
   * 
   * @param type $status
   * @param type $message
   * @param type $level 
   */
  
  function logIt($message, $level = 0, $status = 'System')
  {
    if ($level > $this->logLvl || !$this->debugIS) 
      return false;
    
    $this->openFile($this->logFile, 'logFileStrm');  
    
    if ($this->filesStrms['logFileStrm'])
    { 
      return $this->returnAnswer(fwrite($this->filesStrms['logFileStrm'], date('Y/m/d H:i:s -> ').$status.': '.trim($message)."\n"));
    }
  }
  
  /**
   *
   * функция открытия файла соотственно заданому пути в заданную переменную, с возможностью изменения аттрибутов доступа
   * 
   * @param type $file
   * @param type $stream
   * @param type $attr
   * @return type 
   */
  
  protected function openFile($file, $stream, $attr = 'ab')
  {
    if (!isset($this->filesStrms[$stream]))
    {
      $this->filesStrms[$stream] = fopen($file, $attr);
      return $this->returnAnswer($this->filesStrms[$stream]);
    }
    else
    {
      return true;
    }
  }
  
  /**
   *
   * закрытие файла по назваию переменной
   * 
   * @param type $stream
   * @return type 
   */
  
  protected function closeFile($stream)
  {
    if ($this->filesStrms[$stream])
    {
      return fclose($this->filesStrms[$stream]);
    }
  }
  
  /**
   * 
   * закрывает все открытые файлы указатели на потоки которых присутствуют в массиве объекта filesStrms
   * 
   */
  
  protected function closeAllFiles()
  {
    if (!$this->fileStrms) return false;
    
    foreach ($this->filesStrms as $strmKey => $stream)
    {
      @fclose($stream);
    }
  }
  
  
  /**
   * 
   * функция вызываемая в начале работы таска
   * 
   */
  function start()
  {
    $this->startTime();
    $this->logIt("Task started\n", 1);
  }
  
  
  /**
   * 
   * функция вызываемая в конце для закрытия файлов или всего того что надо сделать после выполнения таска
   * 
   */
  
  function finish()
  {
    if ($this->debugIS) 
    {
      $this->logIt($this->finishTime(), 1);
      $this->logIt($this->showMemory(), 1);
    }
    
    $this->logIt("Task finished\n", 1);
    
    $this->closeAllFiles();
  }
  
  /**
   *
   * проверка на пустоту переменной и сразу возвращение или ее или false
   * 
   * @param mixed $value 
   */
  
  protected function returnAnswer($value)
  {
    if ($value)
    {
      return $value;
    }
    else
    {
      return false;
    }
  }
}
