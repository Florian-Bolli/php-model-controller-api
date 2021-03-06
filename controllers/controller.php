<?php

/**
 * Object Controllers reachable through the API should extend the Controller class, to add standard functions and the process_request function
 */
class Controller
{

    //Request properties
    private $request_method;
    private $object;
    private $objects;
    private $id;

    //Rights
    public $right_create;
    public $right_get;
    public $right_index;
    public $right_update;
    public $right_delete;

    function __construct($object, $objects, $request_method, $id)
    {
        $this->request_method = $request_method;
        $this->object = $object;
        $this->objects = $objects;
        $this->id = $id;
    }

    /**
     * Processes the standard requests: 
     * Some standard (get, index, create, update, delete ) methodes are already implemented
     * The rights have to be set for  the functions to work
     */
    function process_request($request_type)
    {

        //Non standard request
        if ($request_type != NULL) {
            if (method_exists($this, $request_type)) {
                $this->$request_type();
                return;
            } else {
                $this->functionDoesNotExist();
            }
        }
        //Standard requests
        switch ($this->request_method) {
            case "POST":
                $this->create();
                break;
            case "GET":
                if ($this->id) {
                    $this->get($this->id);
                } else {
                    $this->index();
                };
                break;
            case "PUT":
                $this->update();
                break;
            case "DELETE":
                $this->delete($this->id);
                break;
        }
    }


    /** Outputs one single object from DB (with id) */
    function get($id)
    {
        if (!$this->right_get) $this->notAuthorized();

        try {
            $object = $this->objects->get_by_id($id);
            echo $object->text();
        } catch (Exception $e) {
            print("Error. $e No cat with id $id.");
        }
    }

    /** Save a new object into DB, with the declared atributes */
    function create()
    {
        if (!$this->right_create) $this->notAuthorized();

        $post = json_decode(file_get_contents('php://input'), true);
        $this->object->overwrite_atributes($post);
        if ($id = $this->object->save()) {
            $object_name = get_class($this->object);
            echo "Success. $object_name saved with id $id.";
        }
    }




    function update()
    {
        if (!$this->right_update) $this->notAuthorized();

        $post = json_decode(file_get_contents('php://input'), true);
        $this->object->overwrite_atributes($post);
        try {
            echo json_encode($this->object);
            $this->object->update();
            $object_name = get_class($this->object);
            echo "Success. $object_name has been updated.";
        } catch (Exception $e) {
            echo "Error: $e";
        }
    }


    /** Deletes the object with ID from DB */
    function delete($id)
    {
        if (!$this->right_delete) $this->notAuthorized();

        $object_name = get_class($this->object);
        $this->objects->delete_by_id($id);
        echo "Success. $object_name with id $id has been deleted.";
    }


    /** Outputs a list of all stored objects */
    function index()
    {
        if (!$this->right_index) $this->notAuthorized();

        $cats = $this->objects->get_all();
        echo json_encode($cats);
    }



    //Standard outputs$
    function notAuthenticated()
    {
        echo '{"Error": "Not authenticated"}';
        die();
    }

    function notAuthorized()
    {
        echo '{"Error": "Not authorized"}';
        http_response_code(403);
        die();
    }

    function functionDoesNotExist()
    {
        echo '{"Error": "Function does not exist"}';
        die();
    }
}