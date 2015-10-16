host:172.16.18.226

http://host:8848/create_user
{
  "username": "85263007733",
  "operatorId": "voip_directel_com_hk",
  "regMsisdn": "85263007733",
  "simInfo": "454111234123421"
}

result:
{
  "smsVerificationFlag": false,
  "userType": "PCCW_601",
  "featureClass": 0,
  "username": "58234834",
  "alternateMsisdnList": [
    {
      "msisdn": "8521612345678",
      "countryCode": "852",
      "isHPLMNNumber": true,
      "imsi": "45411161234567"
    }
  ],
  "state": "ACTIVE",
  "errorCode": "000"
}


http://host:8848/set_secure
{
  "username": "85263007733",
  "operatorId": "voip_directel_com_hk",
  "regMsisdn": "85263007733",
  "simInfo": "454111234123421",
  "sSipStatus": true
}

result:
{"errorCode": "000"}


http://host:8848/get_user
{"userid": "81542738"}

result:
{
  "error_code":"000",
  "userid":"81542738",
  "username":"8521612345678",
  "feature":"1,2,4,8,16",
  "siminfo":"454111234123421",
  "msisdn":"8521613345678",
  "countyrcode":"852",
  "zgtflag":"N",
  "usertype":"PCCW_601",
  "operatorid":"voip_directel_com_hk",
  "createtime":"2014-11-08 21:09:11",
  "score":"500",
  "activetime":"2014-11-08 21:09:11",
  "amount":"0.00",
  "validdate":"2099-12-31",
  "status":"A",
  "nextkfdate":"2099-12-31"
}


http://host:8848/recharge
{"userid": "38029619", "cardno": "12345669894844994949"}

result:
{"error_code":"000"}


http://host:8848/voip_jq
{"msisdn": "85263007733", "precode": "1606"}

result
{"error_code": "000", "rate_code": "200"}


http://host:8848/voip_kf
{
  "msisdn": "85263007733",
  "callnoa": "85238029619",
  "callnob": "1606862087521734",
  "amount": 0.01,
  "duration": 123,
  "callid": "85238029619_MOC_20141117-122222_001",
  "calltype": "1606"
}

result:
{"error_code": "M01", "error_msg", "balance is not enough"}
