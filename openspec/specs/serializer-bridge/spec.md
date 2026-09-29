# serializer-bridge Specification

## Purpose
How opaque values cross Symfony Serializer: ciphered on the way out, restored and typed on the way
in, and refused when they cannot be. Decided in OPQ002.
## Requirements
### Requirement: A serialized payload carries secrets ciphered

With the bridge registered, serializing an object holding an opaque value SHALL write the secret
ciphered, and deserializing SHALL restore an opaque value of the declared type.

#### Scenario: Credentials through JSON

- **WHEN** an application serializes credentials holding the password `Lorem ipsum dolor` to JSON
- **THEN** the JSON does not contain `Lorem ipsum dolor`
- **AND** deserializing that JSON gives back credentials whose password discloses `Lorem ipsum dolor`

### Requirement: The bridge fails closed

A row the configured keys did not write, or a clear string where a ciphered row is expected, SHALL
be refused. The error SHALL NOT contain the value.

#### Scenario: A secret written in clear into a journal

- **WHEN** a payload holds the password as a plain string
- **THEN** deserialization fails
- **AND** the error message does not contain the string

### Requirement: Without the bridge, nothing leaks

A serializer assembled without the bridge SHALL NOT write the secret: the stock object and property
normalizers SHALL either write nothing for it or refuse.

#### Scenario: A host that forgot to register the bridge

- **WHEN** credentials are serialized with only the stock object normalizer
- **THEN** the output does not contain the password
