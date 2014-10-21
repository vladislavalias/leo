<?php

error_reporting(E_ALL);
ini_set("display_errors", "1");

include('LogHistory.class.php');
include('simpleSender.class.php');
include('dictionary.php');


$file = __DIR__.'/log/log.log';

$logger = new LogHistory($file, true, 30);
$sender = new simpleSender();

//<form data-register-form class="au-form" action="/ru/register" method="POST" data-form="reg">
//  180
//<input type="hidden" name="r_timezone" value="" id="r_timezone" />
//<input type="email" name="r_email" value="" autocomplete="off" class="placeholder-light au-form-input" placeholder="Введите email" id="r_email" />
//<input type="password" name="r_password" autocomplete="off" class="placeholder-light au-form-input" placeholder="Придумайте пароль" id="r_password" />
//<input type="submit" value="Создать аккаунт" class="btn-upper-orange btn-big au-form-btn"/>

$data = [
    'r_timezone'  => 180,
    'r_email'     => generateEmail(4),
    'r_password'  => generatePassword(10),
//    0             => 'Создать аккаунт'
];

dump($data, false);

$sender->setData($data);
$firstAnswer = $sender->getRaw('lingualeo.com/ru/register', 'post');
dump($firstAnswer, false);
$cookies = extractCookies($sender->splitResult($firstAnswer), 'Set-Cookie');
$sender->setHeaders(['Cookie: '.implode('; ', $cookies) . "\r\n"]);
//$sender->setData($data);
dump($sender->getRaw('lingualeo.com/ru/survey/step/1'));

function dump($value, $exit = true)
{
  var_dump($value);
  if ($exit) exit();
}

function extractCookies($text, $name)
{
  $head = $text['head'];
  $exploded = explode("\n", $head);
  $result = [];
  
  foreach ($exploded as $one)
  {
    $trimed = trim($one);
    
    if (false === strpos($trimed, ':')) continue;
    
    list($headName, $headValue) = explode(': ', $trimed, 2);
    
    if ($name == $headName && false === strpos($headValue, 'deleted'))
    {
      list($first, ) = explode(';', $headValue, 2);
      $result[] = trim($first);
    }
  }
  
  return $result;
}

function generatePassword($length)
{
  return generateString($length);
}

function generateEmail($length)
{
  $domains = [
      'gmail.com',
      'yahoo.com',
      'mail.ru',
      'yandex.ru',
      'inbox.ru',
      'list.ru',
  ];
  
  global $dictionary;
  
  return sprintf('%s%s@%s', extractRandom($dictionary), generateString($length), extractRandom($domains));
}

function extractRandom($array)
{
  $sizeof = sizeof($array) - 1;
  $index = mt_rand(0, $sizeof);
  
  return $array[$index];
}

function generateString($length)
{
  $password = '';
  $possible = "0123456789abcdfghjkmnpqrstvwxyzABCDFGHJKMNPQRSTVWXYZ";
  $i = 0;
  while ($i < $length)
  {

    // pick a random character from the possible ones
    $char = substr($possible, mt_rand(0, strlen($possible)-1), 1);

    // we don't want this character if it's already in the password
    if (!strstr($password, $char))
    {
      $password .= $char;
      $i++;
    }
  }
  return $password;
}