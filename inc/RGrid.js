 /**
  * The row mouseover function
  */
function MouseOver(obj)
{
  obj.className = obj.className += ' mouseover';
}
 
 /**
 * the row mouseout function
 */
function MouseOut(obj)
{
   obj.className = obj.className.replace(/ mouseover/, '');
}


