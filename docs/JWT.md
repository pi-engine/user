# Create a JWT #
### By public and private keys ###

1- Create a private key
```
openssl genpkey -algorithm RSA -out private_key.pem -pkeyopt rsa_keygen_bits:2048
```

2- Create a public key
```
openssl rsa -pubout -in private_key.pem -out public_key.pem
```

3- Update config file