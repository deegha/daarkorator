# daarkorator

## Project setup
	- Clone the repository
	- Run composer install to install the dependancies
	

### API Endpoints

#### login

- `/login`
- method : post
- request 
	`{
	"email": "shuboothi@gmail.com",
	"password" : "098f6bcd4621d373cade4e832627b4f6",
	"type"	: 1
	}`

- response with no errors
	`{"error":false,"accessToken":"9f27c285e5cddd59abb8970102f25da6","username":"deegha","message":"Successfully authenticated"}`


#### userFeatures

- `/userFeatures`
- method : get
- headers : Authorization
- resquest : 
- response with no errors

`{
    "error": false,
    "features": {
        "0": "1",
        "1": "{\"create_user\" : true, \"update_user\" : true, \"delete_user\" : true, \"list_users\":true ,\"create_project\" : true, \"update_project\" : true, \"delete_project\":true, \"list_projects\" : true, \"update_price\" :true}",
        "id": "1",
        "features": "{\"create_user\" : true, \"update_user\" : true, \"delete_user\" : true, \"list_users\":true ,\"create_project\" : true, \"update_project\" : true, \"delete_project\":true, \"list_projects\" : true, \"update_price\" :true}"
    }
}`

#### create user

- `/user`  
- method  : post 
- headers : Authorization
- request 
`{
"first_name" : 	"jone",
"last_name"	:"smith",
"email": "shuboothi@gmail.com",
"password" : "098f6bcd4621d373cade4e832627b4f6",
"user_type" : 1,
"user_image" : "https://www.atomix.com.au/media/2015/06/atomix_user31.png",
"contact_number" : "0322222623",
"daarkorator_details" : {
	"company_name" : "jadopado",
	"about"	: "Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s",
	"tranings" : "Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500",
	"tools" : "Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500",
	"instagrame":"https://www.instagram.com/?hl=en",
	"website": "test.com"
	}
}`

- response with no errors
`{"error":false,"user_id":14}`

