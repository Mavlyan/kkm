# === How to test KKM module. ===

## I. Prepare config.
open `System -> Configuration -> KKM module` of your Magento store

Fill:
*    Kkm login
*    Kkm passwd
*    Atol Group Code
*    List of payment methods
*    `Use default shipping name` = No
*    `Custom shipping name` = enter here something interesting
*    `Debug` = Yes (the last dropdown on config page)
*    Other settings ( they seem obvious)

## II. Common steps and results
**Common step for all cases:**
* . Create an order. Payment should be equal to selected one from "List of payment methods" (see KKM config)

**Common result of all tests:**
* 1. Open table 'mygento_kkm_statuses'
* 2. New row should be added. 
* 3. Check the row.
  *  1. `external_id` contain entity name (invoice or creditmemo) and entity id.
  *  2.  `status` should contain json_encoded response from KKM provider (currently ATOL). 

## III. Conditions
###      Test PASSED
if json (from 3.2) contains:

            "status": "done" (or "wait)
            "error": null

**Example of OK json (3.2):**

```json
{  
   "callback_url":"http://demoee.mygento.ru/index.php/kkm/index/callback/",
   "error":null,
   "payload":{  
      "ecr_registration_number":"00000001234567890",
      "fiscal_document_attribute":1234567890,
      "fiscal_document_number":76,
      "fiscal_receipt_number":9,
      "fn_number":"999901234567890",
      "receipt_datetime":"03.05.2017 15:40:00",
      "shift_number":21,
      "total":230000.44
   },
   "status":"done",
   "timestamp":"03.05.2017 15:39:46",
   "uuid":"18df9c51-8548-498d-9a52-289b254a45c5"
}
```

#### Test FAILED
if json (from 3.2) contains:

            "status": "fail"
            "error": {
                smthg here
                     }

**Example of Failed json (3.2):**

```json
{  
   "uuid":"4268d569-a6f6-44e8-be95-89d4490c063a",
   "timestamp":"19.05.2017 14:11:34",
   "status":"fail",
   "error":{  
      "code":8,
      "text":"Ошибка валидации входящего чека с GUID \"4268d569-a6f6-44e8-be95-89d4490c063a\": NotAnyOf: #/\n{\n  ArrayExpected: #/receipt.items\n}\n{\n  PropertyRequired: #/correction\n}\n",
      "type":"system"
   },
   "payload":null
}
```

## II. Sort of test cases:
### 1.Full invoice.
**Scenario:**
* Create full invoice for order.
* Submit it.
* Check it (see [III. COnditions](#III_Conditions_19)

### 2.Partial invoice.
**Scenario:**
* Create partial invoice for order (not all products should be invoiced).
* Submit it.
* Check it (see [III. COnditions](#III_Conditions_19)

### 3.Full creditmemo
**Scenario:**
* Create full creditmemo for order, which was previously invoiced.
* Submit it.
* Check it (see [III. COnditions](#III_Conditions_19)

### 4.Partial creditmemo
**Scenario:**
* Create full creditmemo for order, which was previously invoiced.
* Submit it.
* Check it (see [III. COnditions](#III_Conditions_19)

### 5.Another payment method
**Scenario:**
* Create an order with payment method which is NOT selected in KKM settings.
* Submit it.
* Create an invoice.
* Submit invoice.

**Expected result:**
New row in DB `mygento_kkm_status` should not be added.

### 6.Proper shipping name
**Scenario:**
* Do steps from test case 3.

**Expected result:**
Open log file of the module:
Path: `root-of-the-shop/var/log/kkm.log`

There are a lot of logged KKM requests. Find one of them by string `jsonPost`.
This logged jsonPost should contain your custom shipping name (from I. Prepare config).

**Example:**
2017-05-26T17:57:54+00:00 DEBUG (7): cancelCheque jsonPost: {"external_id":"creditmemo_100000022","service":{"payment_address":"example.com","callback_url":"http:\/\/demoee.mygento.ru\/index.php\/kkm\/index\/callback\/","inn":"0123456788"},"timestamp":"26-05-2017 20:57:54","receipt":{"attributes":{"sno":"osn","phone":"8-909-900-0990","email":"d.g.r.o.m.o.v@mygento.ru"},"total":8773.8,"payments":[{"sum":8773.8,"type":1}],"items":[{"price":7862.4,"name":"U Pure","quantity":1,"sum":7862.4,"tax":"vat0"},{"price":855.39,"name":"TANIM de Chiapas Mexico ","quantity":1,"sum":855.39,"tax":"vat0"},{"price":56,"name":"Envivo Lungo","quantity":1,"sum":56,"tax":"vat0"},{"name":" **YOUR CUSTOM SHIPPING NAME** ","price":0.01,"quantity":1,"sum":0.01,"tax":"vat0"}]}}
