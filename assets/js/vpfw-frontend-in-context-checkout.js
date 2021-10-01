
// Haven't fully tested this because of button style transition issue
function processVPFWCheckout(VPApp) 
{
  var VPAppElem = document.getElementById(VPApp);
  var DisplayMode = VPAppElem.style.display;

  if (DisplayMode == "block")
  {
    VPAppElem.style.display = "none";
  }
  else
  {
    VPAppElem.style.display = "block"; 
  }

  var divText = VPAppElem.outerHTML;
  var myWindow = window.open('','','width=400,height=600');
  var doc = myWindow.document;
  var tabOrWindow = doc.open();
  doc.write(divText);
  doc.close();
  tabOrWindow.focus();
}
