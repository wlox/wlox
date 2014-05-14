(function( $ ){
  // Simple wrapper around jQuery animate to simplify animating progress from your app
  // Inputs: Progress as a percent, Callback
  // TODO: Add options and jQuery UI support.
  $.fn.animateProgress = function(progress, callback) {    
    return this.each(function() {
      $(this).animate({
        width: progress+'%'
      }, {
        duration: 2000, 
        
        // swing or linear
        easing: 'swing',

        // this gets called every step of the animation, and updates the label
        step: function( progress ){
          var labelEl = $('.ui-label', this),
              valueEl = $('.value', labelEl);
          
          if (Math.ceil(progress) < 20 && $('.ui-label', this).is(":visible")) {
            labelEl.hide();
          }else{
            if (labelEl.is(":hidden")) {
              labelEl.fadeIn();
            };
          }
          
          if (Math.ceil(progress) == 100) {
            labelEl.text('Done');
            setTimeout(function() {
              labelEl.fadeOut();
            }, 1000);
          }else{
            valueEl.text(Math.ceil(progress) + '%');
          }
        },
        complete: function(scope, i, elem) {
          if (callback) {
            callback.call(this, i, elem );
          };
        }
      });
    });
  };
})( jQuery );

$(function() {
  // Hide the label at start
  $('#progress_bar .ui-progress .ui-label').hide();
  // Set initial value
  $('#progress_bar .ui-progress').css('width', '7%');

  // Simulate some progress
  $('#progress_bar .ui-progress').animateProgress(81, function() {
    $(this).animateProgress(81, function() {
      setTimeout(function() {
        $('#progress_bar .ui-progress').animateProgress(81, function() {
          $('#main_content').slideDown();
          $('#fork_me').fadeIn();
        });
      }, 2000);
    });
  });
  
});


$(function() {
  // Hide the label at start
  $('#progress_bar2 .ui-progress .ui-label').hide();
  // Set initial value
  $('#progress_bar2 .ui-progress').css('width', '7%');

  // Simulate some progress
  $('#progress_bar2 .ui-progress').animateProgress(63, function() {
    $(this).animateProgress(63, function() {
      setTimeout(function() {
        $('#progress_bar2 .ui-progress').animateProgress(63, function() {
          $('#main_content').slideDown();
          $('#fork_me').fadeIn();
        });
      }, 2000);
    });
  });
  
});


$(function() {
  // Hide the label at start
  $('#progress_bar3 .ui-progress .ui-label').hide();
  // Set initial value
  $('#progress_bar3 .ui-progress').css('width', '7%');

  // Simulate some progress
  $('#progress_bar3 .ui-progress').animateProgress(85, function() {
    $(this).animateProgress(85, function() {
      setTimeout(function() {
        $('#progress_bar3 .ui-progress').animateProgress(85, function() {
          $('#main_content').slideDown();
          $('#fork_me').fadeIn();
        });
      }, 2000);
    });
  });
  
});


$(function() {
  // Hide the label at start
  $('#progress_bar4 .ui-progress .ui-label').hide();
  // Set initial value
  $('#progress_bar4 .ui-progress').css('width', '7%');

  // Simulate some progress
  $('#progress_bar4 .ui-progress').animateProgress(90, function() {
    $(this).animateProgress(90, function() {
      setTimeout(function() {
        $('#progress_bar4 .ui-progress').animateProgress(90, function() {
          $('#main_content').slideDown();
          $('#fork_me').fadeIn();
        });
      }, 2000);
    });
  });
  
});