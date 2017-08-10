# daarkorator

## Project setup
	- Clone the repository 
	- Run database script

### API Endpoints

#### login

- `/login`
- method : post
- request 
	`{
	"email": "shuboothi@gmail.com",
	"password" : "098f6bcd4621d373cade4e832627b4f6"
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
    {"error":false,"features":{"create_user":true,"update_user":true,"delete_user":true,"list_users":true,"create_project":true,"update_project":true,"delete_project":true,"list_projects":true,"update_price":true}}
    }
}`

#### create user

- `/user`  
- method  : post 
- headers : Authorization
- Note : `"save_project" : true ` for save project
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

#### List users

- `/user`
- method  : get 
- headers : Authorization
- response with no errors
`{
    "error": false,
    "users": [
        {
            "id": "3",
            "first_name": "jone",
            "last_name": "smith",
            "email": "shuboothi@gmail.com",
            "user_image": "https://www.atomix.com.au/media/2015/06/atomix_user31.png",
            "contact_number": "0322222623",
            "company_name": "jadopado",
            "about": "Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s",
            "tranings": "Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500",
            "tools": "Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500",
            "instagrame": "https://www.instagram.com/?hl=en",
            "website": "test.com"
        },
        {
            "id": "1",
            "first_name": "deegha",
            "last_name": "galkissa",
            "email": "shuboothi@gmail.com",
            "user_image": "",
            "contact_number": "0322222623",
            "company_name": null,
            "about": null,
            "tranings": null,
            "tools": null,
            "instagrame": null,
            "website": null
        }
    ]
}`

#### Delete user

- `/user/:user_id`
- method  : delete 
- headers : Authorization
- response with no errors
`{
    "error": false,
    "message": "User Successfully Deleted"
}`


#### Update user

- `/user/:user_id`
- method  : put 
- headers : Authorization
- response with no errors
`{"error":false,"message":"User updated Successfully"}`
- request body 

`
{
"first_name" :  "deegha",
"last_name" :"Galkissa",
"update_password":true,
"password" : "098f6bcd4621d373cade4e832627b4f6",
"user_image" : "https://www.atomix.com.au/media/2015/06/atomix_user31.png",
"contact_number" : "0322222623",
"daarkorator_details" : {
    "company_name" : "jadopado",
    "about" : "Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s",
    "tranings" : "Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500",
    "tools" : "Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500",
    "instagrame":"https://www.instagram.com/?hl=en",
    "website": "test.com"
}
}`

** send request body without `daarkorator_details` attribute for non daarkorator users


#### forgot password

- `/forgotPassword`
- method  : post 
- headers : non
- request :`{"email":"shuboothi@gmail.com"}`
- response with no errors
`{"error":false,"message":"Email sent Successfully"}`


#### Update package

- `/package`
- method  : put
- headers : Authorization
- request : {
            	"price":"23"
            }
- response with no errors
`{"error":false,"message":"Package updated successfully"}`

#### User Signup

-userSignUp
-method : post
-headers: non
-request: 
`{
"first_name" :  "jone",
"last_name" :"smith",
"email": "shuboothi4353ds@gmail.com",
"password" : "098f6bcd4621d373cade4e832627b4f6",
"confirm_password" : "098f6bcd4621d373cade4e832627b4f6",
"user_image" : "https://www.atomix.com.au/media/2015/06/atomix_user31.png",
"contact_number" : "0322222623"
}`
- response with no errors
`{"error": false,"message": "User created successfully"}`


#### get Room types

- `/rooms`
- method : get
- headers : none
- resquest :
- response with no errors
{"error":false,"rooms":[{"id":"1","title":"Living room"},{"id":"2","title":"Dining Room"},{"id":"3","title":"Office"},{"id":"4","title":"Master BedRoom"},{"id":"5","title":"Kid's Bedroom"},{"id":"6","title":"Guest BedRoom"},{"id":"7","title":"Nursery"},{"id":"8","title":"Balcony"}]}

#### get Room images

- `/room-images`
- method : get
- headers : none
- resquest :
- response with no errors
{"error":false,"roomImages":[{"id":"1","image_url":"http:\/\/cdn.home-designing.com\/wp-content\/uploads\/2009\/07\/living-room-arrangement.jpg"},{"id":"2","image_url":"http:\/\/www.amazadesign.com\/wp-content\/uploads\/Admirable-White-Living-Room-Design-Ideas-with-Red-Curtains-Furnished-with-Sofa-and-Chairs-Completed-with-Glass-Round-Table-and-Pendant-Lamp-plus-Wall-Flatscreen-TV.jpg"},{"id":"3","image_url":"https:\/\/s-media-cache-ak0.pinimg.com\/originals\/cb\/90\/80\/cb90809a736cf358c8fc804ad6e78b54.jpg"},{"id":"4","image_url":"http:\/\/cdn.home-designing.com\/wp-content\/uploads\/2009\/07\/living-room-arrangement.jpg"}]}

#### get color choices

- `/color-choices`
- method : get
- headers : none
- resquest :
- response with no errors
{"error":false,"roomColors":[{"id":"5","name":"Blues","imageUrl":"http:\/\/s3.amazonaws.com\/colorcombos-images\/users\/1\/color-schemes\/color-scheme-6-main.png?v=20110818210849"},{"id":"6","name":"Reds","imageUrl":"http:\/\/s3.amazonaws.com\/colorcombos-images\/users\/1\/color-schemes\/color-scheme-7-main.png?v=20110818210849"},{"id":"7","name":"Yellows","imageUrl":"http:\/\/s3.amazonaws.com\/colorcombos-images\/users\/1\/color-schemes\/color-scheme-3-main.png?v=20110818210849"},{"id":"8","name":"Purples","imageUrl":"http:\/\/s3.amazonaws.com\/colorcombos-images\/users\/1\/color-schemes\/color-scheme-2-main.png?v=20110818210849"},{"id":"9","name":"Greens","imageUrl":"http:\/\/s3.amazonaws.com\/colorcombos-images\/users\/1\/color-schemes\/color-scheme-8-main.png?v=20110818210849"}]}


#### get user roles

- `/user/types`
- method : get
- headers : Authorization
- resquest :
- response with no errors
{"error":false,"user_types":[{"id":"1","display_name":"Administrator"},{"id":"2","display_name":"Customer"},{"id":"3","display_name":"Daakor"}]}


#### List single user

- `/user/:id`
- method  : get 
- headers : Authorization
- response with no errors
`{  
   "error":false,
   "users":[  
      {  
         "id":"1",
         "first_name":"deegha",
         "last_name":"Galkissa",
         "email":"shuboothi@gmail.com",
         "user_image":"https:\/\/www.atomix.com.au\/media\/2015\/06\/atomix_user31.png",
         "contact_number":"0322222623",
         "status":"1",
         "company_name":null,
         "about":null,
         "tranings":null,
         "tools":null,
         "instagrame":null,
         "website":null
      }
   ]
}`


#### User resetPasword
 * url - /resetpassword
 * method - POST
 * params -user object
 response with no errors
 {
    {"error":false,"message":"Password updated Successfully"}
 }
 */


 #### Create project

- `/u/project`
- method  : post 
- headers : Authorization
- Request
`   {  
       "room":{  
          "id":6,
          "displayName":"Guest Bed Room"
       },
       "designStyle":[  
          {  
             "id":2,
             "imgUrl":"http://cdn.home-designing.com/wp-content/uploads/2009/07/living-room-arrangement.jpg",
             "selected":true
          }
       ],
       "colorChoice":{  
          "likeColors":[  
             {  
                "id":3,
                "name":"Yellows",
                "imgUrl":"http://s3.amazonaws.com/colorcombos-images/users/1/color-schemes/color-scheme-3-main.png?v=20110818210849",
                "selected":true
             }
          ],
          "dislikeColors":"Black Red Yellow"
       },
       "roomDetails":{  
          "projectName":"Test Project name",
          "length":10,
          "width":20,
          "height":30,
          "unit":"m/cm",
          "roomImage":null,
          "budget":"900",
          "furnitureImage":null
       },
       "inspirations":{  
          "urls":[  
             {  
                "id":"ac5635bd-339d-43f5-d18e-43b067909c39",
                "url":"http://localhost:4200/project-wizard"
             },
             {  
                "id":"8a00f503-1ac4-cae0-6018-2375cdc56a1d",
                "url":"https://github.com/deegha/daarkorator/tree/develop"
             },
             {  
                "id":"3621e7f9-98b6-1584-542a-5a7286890d81",
                "url":"http://localhost:3333/#"
             }
          ],
          "description":"down vote\naccepted\nI found the solution. Thanks to the comments on the API site: http://www.asp.net/web-api/overview/security/individual-accounts-in-web-api\n\nI had to set the correct header for application/x-www-form-urlencoded; charset=UTF-8 and serialize the object i posted. I canŽt find an Angular serializer method, so I made my own(copy from another stackoverflow site) in JavaScript"
       }
    }`

    - respose with no errors
    `{
    "error": false,
    "message": "Project successfully created."
}`