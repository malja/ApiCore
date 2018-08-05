import requests
import datetime
import urllib
import hashlib
import hmac

# Link to API endpoint used fo connection
endpoint = "https://jan.malcak.cz/projects/api/versions"

# Private key
private_key = "ca57b5c3-09a0-4b78-a752-7ccc5629287a"

# List of headers
headers = {
    "Auth-Public": "mzoqgskzmChPWqzxpJMN",
    "Auth-Signature": "",
    "Auth-Datetime": datetime.datetime.now().isoformat()
}

# Request body
data = {
    "nonce": 17,
    "test": "abrakadabra"
}

postdata = urllib.parse.urlencode(data)
message = str(data["nonce"]) + postdata

signature = hmac.new(
    private_key.encode("utf-8"),
    message.encode("utf-8"),
    hashlib.sha512
).hexdigest()

# Send request

headers["Auth-Signature"] = signature

result = requests.get(endpoint, params=data, headers=headers)
print(result.text)
