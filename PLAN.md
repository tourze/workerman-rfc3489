# Workerman RFC3489 (STUN协议) 实现计划

## 项目概述

本项目旨在使用 Workerman 框架实现 RFC3489 定义的 STUN (Simple Traversal of UDP through NATs) 协议。STUN 是一种网络协议，允许位于 NAT 后的客户端找出自己的公网地址和端口映射，以便进行 P2P 通信。

## 实现进度

### 已完成组件 ✅

1. **消息层 (Message Layer)**
   - ✅ `StunMessage.php` - STUN消息的基本结构
   - ✅ `MessageFactory.php` - 消息工厂
   - ✅ `Constants.php` - 协议常量定义
   - ✅ `MessageMethod.php` - 消息方法枚举类
   - ✅ `MessageClass.php` - 消息类别枚举类
   - ✅ `AttributeType.php` - 属性类型枚举
   - ✅ `ErrorCode.php` - 错误代码枚举类
   - ✅ `MessageAttribute.php` - 消息属性基类
   - ✅ `Attributes/` - 各种具体的属性实现

2. **传输层 (Transport Layer)**
   - ✅ `StunTransport.php` - 传输抽象接口
   - ✅ `UdpTransport.php` - 基于UDP的传输实现
   - ✅ `TransportConfig.php` - 传输配置类

3. **协议层 (Protocol Layer)**
   - ✅ `StunClient.php` - STUN客户端实现
   - ✅ `NatTypeDetector.php` - NAT类型检测实现
   - ✅ `NatType.php` - NAT类型枚举
   - ✅ `StunServer.php` - STUN服务器实现
   - ✅ `AddressMappingCollector.php` - 地址映射收集器
   - ✅ `Transaction.php` - 事务处理类
   - ✅ `TransactionState.php` - 事务状态枚举

4. **应用层 (Application Layer)**
   - ✅ `StunApplication.php` - 应用基类
   - ✅ `StunConfig.php` - 应用配置类
   - ✅ `SimpleStunClient.php` - 简单的STUN客户端应用
   - ✅ `SimpleStunServer.php` - 简单的STUN服务器应用

5. **辅助工具 (Utilities)**
   - ✅ `BinaryUtils.php` - 二进制数据处理工具
   - ✅ `IpUtils.php` - IP地址工具
   - ✅ `TransactionIdGenerator.php` - 事务ID生成器
   - ✅ `StunLogger.php` - 日志记录接口实现

6. **异常处理**
   - ✅ `StunException.php` - STUN基础异常类
   - ✅ `MessageFormatException.php` - 消息格式异常
   - ✅ `TransportException.php` - 传输层异常
   - ✅ `TimeoutException.php` - 超时异常
   - ✅ `ProtocolException.php` - 协议逻辑异常

7. **示例实现**
   - ✅ `nat_type_example.php` - NAT类型检测示例
   - ✅ `get_public_ip.php` - 获取公网IP地址示例
   - ✅ `server_example.php` - STUN服务器示例
   - ✅ `simple_client_example.php` - 简单STUN客户端应用示例
   - ✅ `simple_server_example.php` - 简单STUN服务器应用示例
   - ✅ `mapping_collector_example.php` - 地址映射收集器示例

### 待实现组件 ❌
* 无

## 架构分层

### 1. 消息层 (Message Layer)

负责 STUN 消息的编码解码和基本结构处理。

- `Message/`: 存放消息相关类
  - `StunMessage.php`: STUN 消息的基本结构
  - `MessageType.php`: 消息类型定义（请求、响应、错误）
  - `MessageMethod.php`: 消息方法枚举类（Binding, Shared Secret）
  - `MessageClass.php`: 消息类别枚举类（Request, Response, Error Response）
  - `AttributeType.php`: 属性类型枚举（MAPPED-ADDRESS, RESPONSE-ADDRESS等）
  - `ErrorCode.php`: 错误代码枚举类（400, 401, 420, 430, 431, 432, 500等）
  - `MessageAttribute.php`: 消息属性基类
  - `Attributes/`: 各种具体的属性实现
    - `MappedAddress.php`: MAPPED-ADDRESS 属性
    - `ResponseAddress.php`: RESPONSE-ADDRESS 属性
    - `ChangeRequest.php`: CHANGE-REQUEST 属性
    - `SourceAddress.php`: SOURCE-ADDRESS 属性
    - `ChangedAddress.php`: CHANGED-ADDRESS 属性
    - `ErrorCode.php`: ERROR-CODE 属性
    - `UnknownAttributes.php`: UNKNOWN-ATTRIBUTES 属性
    - `ReflectedFrom.php`: REFLECTED-FROM 属性
    - `Username.php`: USERNAME 属性
    - `Password.php`: PASSWORD 属性
    - `MessageIntegrity.php`: MESSAGE-INTEGRITY 属性
  - `MessageFactory.php`: 消息工厂，用于创建各种类型的消息
  - `Constants.php`: 协议常量定义（魔术字节、头部长度等）

### 2. 传输层 (Transport Layer)

基于 Workerman 实现 UDP 传输。

- `Transport/`: 传输相关类
  - `StunTransport.php`: 传输抽象接口
  - `UdpTransport.php`: 基于 Workerman UDP 的传输实现
  - `TransportConfig.php`: 传输配置类

### 3. 协议层 (Protocol Layer)

实现 STUN 协议逻辑，处理客户端和服务器端的交互流程。

- `Protocol/`: 协议实现类
  - `StunClient.php`: STUN 客户端实现
  - `StunServer.php`: STUN 服务器实现
  - `NatDetector.php`: NAT 类型检测实现
  - `AddressMappingCollector.php`: 地址映射收集器
  - `NatType.php`: NAT 类型枚举（Full Cone, Restricted Cone, Port Restricted Cone, Symmetric）
  - `Transaction.php`: 事务处理类

### 4. 应用层 (Application Layer)

提供方便的应用接口和具体的应用实现。

- `Application/`: 应用层类
  - `StunApplication.php`: 应用基类
  - `SimpleStunClient.php`: 简单的 STUN 客户端应用
  - `SimpleStunServer.php`: 简单的 STUN 服务器应用
  - `StunConfig.php`: 应用配置类

### 5. 辅助工具 (Utilities)

提供各种辅助功能。

- `Utils/`: 辅助工具类
  - `BinaryUtils.php`: 二进制数据处理工具
  - `IpUtils.php`: IP 地址工具
  - `TransactionIdGenerator.php`: 事务 ID 生成器
  - `StunLogger.php`: 基于 PSR/Log 的日志记录接口实现

### 6. 异常处理

定义各类异常以便更好地处理错误。

- `Exception/`: 异常类
  - `StunException.php`: STUN 基础异常类
  - `MessageFormatException.php`: 消息格式异常
  - `TransportException.php`: 传输层异常
  - `ProtocolException.php`: 协议逻辑异常
  - `TimeoutException.php`: 超时异常

## 测试实现

测试将遵循单元测试最佳实践，采用 PHPUnit 框架实现。

### 测试结构

- `tests/Unit/`: 单元测试
  - `Message/`: 消息层测试
    - `StunMessageTest.php`
    - `MessageAttributeTest.php`
    - `Attributes/`: 属性测试
  - `Transport/`: 传输层测试
  - `Protocol/`: 协议层测试
  - `Utils/`: 工具类测试

- `tests/Integration/`: 集成测试
  - `ClientServerTest.php`: 客户端和服务器交互测试
  - `NatTypeDetectionTest.php`: NAT 类型检测测试

- `tests/Functional/`: 功能测试
  - `StunClientAppTest.php`
  - `StunServerAppTest.php`

- `tests/Performance/`: 性能测试
  - `MessageBenchmarkTest.php`: 消息处理性能测试
  - `ClientBenchmarkTest.php`: 客户端性能测试
  - `ServerBenchmarkTest.php`: 服务器性能测试

### 测试策略

1. **单元测试**：测试各个组件的独立功能
   - 消息编码解码
   - 属性处理
   - 工具类功能

2. **集成测试**：测试组件间的交互
   - 客户端与服务器交互
   - NAT 类型检测过程

3. **功能测试**：测试整体应用功能
   - 模拟真实网络环境的测试
   - 边界情况测试

4. **模拟测试**：使用模拟对象模拟网络和 NAT 行为

5. **性能测试**：测试系统性能和可扩展性
   - 高并发测试
   - 资源使用测试

## 依赖管理

项目将使用 Composer 管理依赖，关键依赖包括：

1. **workerman/workerman**：核心网络库
2. **psr/log**：日志接口标准
3. **monolog/monolog**：可选的日志实现
4. **phpunit/phpunit**：测试框架

## 项目文档

除代码注释外，项目将包含以下文档：

1. **README.md**：项目概述和快速入门指南
2. **docs/api/**：API 文档
3. **docs/examples/**：使用示例
4. **docs/nat-types.md**：NAT 类型说明文档
5. **docs/protocol.md**：协议实现细节文档

## 开发阶段规划

1. **阶段一：基础设施搭建**
   - 实现消息层基础结构
   - 实现基本的传输层
   - 时间：1-2周

2. **阶段二：客户端功能实现**
   - 实现 STUN 客户端核心功能
   - 实现地址发现功能
   - 时间：1-2周

3. **阶段三：服务器功能实现**
   - 实现 STUN 服务器核心功能
   - 实现消息处理和响应生成
   - 时间：1-2周

4. **阶段四：NAT 类型检测**
   - 实现完整的 NAT 类型检测算法
   - 添加 NAT 行为分析功能
   - 时间：1-2周

5. **阶段五：测试和文档**
   - 编写单元测试和集成测试
   - 完善文档和示例
   - 时间：1-2周

6. **阶段六：优化和扩展**
   - 性能优化
   - 添加扩展功能
   - 错误处理完善
   - 时间：1-2周

## CI/CD集成

项目将集成以下 CI/CD 工具和流程：

1. **Github Actions**：自动化测试和构建
2. **PHPStan**：静态代码分析
3. **PHP_CodeSniffer**：代码风格检查
4. **Codecov**：代码覆盖率分析

## 参考文档

1. **RFC 文档**
   - [RFC 3489: STUN - Simple Traversal of User Datagram Protocol (UDP) Through Network Address Translators (NATs)](https://tools.ietf.org/html/rfc3489)

2. **相关 RFC 文档**
   - [RFC 5389: Session Traversal Utilities for NAT (STUN)](https://tools.ietf.org/html/rfc5389) - RFC3489 的后续版本
   - [RFC 5780: NAT Behavior Discovery Using STUN](https://tools.ietf.org/html/rfc5780)

3. **Workerman 文档**
   - [Workerman 官方文档](https://www.workerman.net/doc/workerman/)
   - [Workerman UDP 文档](https://www.workerman.net/doc/workerman/protocols/how-protocols.html)

4. **网络和 NAT 相关资料**
   - [NAT 类型介绍](https://www.think-like-a-computer.com/2011/09/19/understanding-the-different-types-of-nat/)
   - [P2P 通信与 NAT 穿透技术详解](https://www.cnblogs.com/unruledboy/archive/2010/12/11/P2P_Chat_NAT_Traversal.html)

5. **开源实现参考**
   - [PHP STUN Client](https://github.com/gabrielrcouto/php-stun-client)
   - [Node.js STUN implementation](https://github.com/enobufs/stun)

6. **PSR 标准**
   - [PSR-3: Logger Interface](https://www.php-fig.org/psr/psr-3/)
   - [PSR-4: Autoloader](https://www.php-fig.org/psr/psr-4/)
   - [PSR-12: Extended Coding Style](https://www.php-fig.org/psr/psr-12/)

## 注意事项和限制

1. RFC3489 是 STUN 协议的早期版本，已被 RFC5389 取代。本项目专注于实现 RFC3489，但设计时会考虑未来可能升级到 RFC5389 的兼容性。

2. STUN 协议在某些复杂 NAT 环境中可能无法正常工作，特别是对称型 NAT (Symmetric NAT)。

3. 本实现主要关注 UDP 传输，不包括 TCP 传输实现。

4. 在实际应用中，可能需要结合 TURN 和 ICE 协议来提供完整的 NAT 穿透解决方案。

5. 性能考量：在高并发环境下需要注意资源使用和性能优化。

## 安全性考虑

1. 实现 MESSAGE-INTEGRITY 属性以确保消息完整性
2. 采用适当的随机数生成器生成事务 ID
3. 防范可能的反射攻击
4. 实现服务器端的请求限制，避免资源耗尽攻击

## 未来扩展方向

1. 升级到 RFC5389 (STUN)
2. 添加 TURN 协议支持 (RFC5766)
3. 实现 ICE 框架 (RFC5245)
4. 添加 WebRTC 支持
5. 支持更多传输协议 (TCP, TLS)
6. 添加服务器集群支持
7. 实现更高级的 NAT 穿透技术
