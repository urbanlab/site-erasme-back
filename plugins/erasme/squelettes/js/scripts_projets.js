function distributeFields()
{
    var radius = 160;

    $('#section-tree .tree').each(
        function () {

            // step = (1.5*Math.PI) / fields.length

            var fields = $(this).find('ul li'),
            container = $(this);

            var width = container.width(),
            height = container.height(),
            angle = parseInt($(this).data('angle')),
            step = (.28 * Math.PI);

            if (fields.length > 5) {
                step = (1.5 * Math.PI) / fields.length;
            }

            fields.each(
                function () {
                    var x = Math.round(width / 2 + radius * Math.cos(angle) - $(this).width() / 2);
                    var y = Math.round(height / 2 + radius * Math.sin(angle) - $(this).height() / 2);

                    $(this).css(
                        {
                            left: x + 'px',
                            top: y + 'px'
                        }
                    );
                    angle += step;
                }
            );

        }
    );
}


function distributeFieldsPrint()
{
    var radius = 75;
    
    $('#print .tree').each(
        function () {
      
            // step = (1.5*Math.PI) / fields.length
    
            var fields = $(this).find('ul li'), container = $(this);
      
            var width = container.width(), height = container.height(),
            angle = parseInt($(this).data('angle')), step = (.28*Math.PI);
          
            if (fields.length > 5) {
                step = (1.5*Math.PI) / fields.length;
            }
          
            fields.each(
                function () {
                    var x = Math.round(width/2 + radius * Math.cos(angle) - $(this).width()/2);
                    var y = Math.round(height/2 + radius * Math.sin(angle) - $(this).height()/2);
          
                    $(this).css(
                        {
                            left: x + 'px',
                            top: y + 'px'
                        }
                    );
                    angle += step;
                }
            );
    
        }
    );
}
  

  // Positionne les element ecosystèmes autour des elements parents
// A $( document ).ready() block.
$(document).ready(
    function () {

        $('.slider').slick(
            {
                infinite: true,
                slidesToShow: 1,
                slidesToScroll: 1,
                autoplay: true,
                autoplaySpeed: 2000,
                draggable: true,
                accessibility: false,
                centerMode: true,
                variableWidth: true,
                arrows : true
            }
        );
        /* Section ecosystème applique les element autour des cercles parents */
        distributeFields()
        distributeFieldsPrint()
    }
);
