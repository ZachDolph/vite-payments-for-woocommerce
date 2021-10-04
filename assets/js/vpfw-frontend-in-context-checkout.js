
function processVPFWCheckout(VPAppElem) 
{
  var divText = VPAppElem.outerHTML;
  var myWindow = window.open('','','width=400,height=400');
  var doc = myWindow.document;
  var tabOrWindow = doc.open();
  doc.write(divText);
  doc.close();
  tabOrWindow.focus();
}