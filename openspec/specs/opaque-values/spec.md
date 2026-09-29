# opaque-values Specification

## Purpose
What an opaque value shows, refuses and hands back: the channels through which a secret must not
leave the process, the single way it does, and how it is stored ciphered and read back after a key
rotation. Decided in OPQ001.
## Requirements
### Requirement: An opaque value does not leak through dumps and exports

An opaque value SHALL NOT expose its content through `var_dump`, `print_r`, `var_export`, an
array cast, instance reflection, or a string cast. A string cast and a dump SHALL
show the mask `**SECRET**`.

#### Scenario: Dumping a secret

- **WHEN** an application builds an opaque value from `Lorem ipsum dolor`
- **AND** passes it to `var_dump`, `print_r` or `var_export`
- **THEN** the output does not contain `Lorem ipsum dolor`

#### Scenario: Logging a secret by accident

- **WHEN** an opaque value is cast to a string
- **THEN** the result is `**SECRET**`

### Requirement: An opaque value refuses to be serialized

`serialize()` and `json_encode()` on an opaque value SHALL fail, rather than write a payload that
loses the value.
`unserialize()` and `__set_state()` SHALL refuse to rebuild one.

#### Scenario: A secret put on a PHP-serialized queue

- **WHEN** an application serializes a message holding an opaque value
- **THEN** the serialization fails at the write, with an error naming the opaque type

#### Scenario: A secret passed into a JSON-journaled payload

- **WHEN** an application JSON-encodes a payload holding an opaque value
- **THEN** the encoding fails, rather than writing an empty object where the secret was

#### Scenario: A forged payload

- **WHEN** a serialized string claiming to be an opaque value with a content is unserialized
- **THEN** the unserialization fails

### Requirement: disclose() is the one way out

The function `disclose()` SHALL return the content of an opaque value, and SHALL be the only public
way to obtain it.

#### Scenario: Handing a secret to the library that needs it

- **WHEN** an application calls `disclose()` on an opaque value built from `s3cr3t`
- **THEN** it receives `s3cr3t`

### Requirement: A typed secret stays typed

An application SHALL be able to declare a named secret type; building one SHALL produce that type,
and deciphering a stored one SHALL restore that type.

#### Scenario: An API key round trip

- **WHEN** an application declares `ApiKey`, builds one, ciphers it and deciphers it as `ApiKey`
- **THEN** it holds an `ApiKey` whose disclosed content is the original

### Requirement: A ciphered row survives key rotation

A ciphered value SHALL record its algorithm and key id. A keyring SHALL cipher with its current key
and decipher a row with the key that wrote it, and SHALL refuse a row none of its keys wrote —
including a clear row, unless an unsecured encoding is on the ring.

#### Scenario: Reading a row written before a rotation

- **WHEN** a row was ciphered with key A
- **AND** the keyring is now key B, then key A
- **THEN** the row deciphers to the original secret

#### Scenario: A row from an unknown key

- **WHEN** a row was ciphered with a key the keyring does not hold
- **THEN** deciphering fails, and the error carries neither the secret nor the key

### Requirement: The ciphered wire format is versioned

The stored form of a ciphered value SHALL carry a version. A row of an unknown version, or with a
missing or malformed field, SHALL be refused rather than read as a wrong value.

#### Scenario: A row from a newer version

- **WHEN** a row with version 2 is read by a component that knows version 1
- **THEN** reading fails
