# swoole-kafka-client
 
## 简介

swoole-kafka-client 是一款基于swoole process是实现的kafka 客户端，继承了swoole协程的高性能特征
默认采用守护进程的方式，可以结合swoole_server & systemd & supervisor 来实现长期运行，自动重启


## 功能

基本实现java版本的全部功能

## 运行环境

- [PHP 7.1+](https://github.com/php/php-src/releases)
- [Swoole 4.4+](https://github.com/swoole/swoole-src/releases)
- [Composer](https://getcomposer.org/)


## 停止运行
```
 kill -15 $masterpid
```

## License

swoole-kafka-client is an open-source software licensed under the MIT
