<?

/*
This class is a connector between wordpress  actions and Click events. It makes it possible to map a WP event to a Click event,
which in turn maps to files in the file system.
*/
class ClickEventDispatcher
{
  function __call($action_name, $args)
  {
    Click::handle_action($action_name, $args);
  }
}

