# eBookStore Service
This is part of a coursework for an eCommerce Module at University. 

The aim is to produce a web service for purchasing books in digital format. The service functionalities are offered by defining entry points (APIs) with specific GET and POST parameters and return JSON results so that different front-ends could be developed independently and other services could interface with it.

The service provides the following functionalities: 

1. Books can be created by administrators of the service (admins) using the create_book API.  Read access to information about books (title, authors, description, cover image, price and  reviews) is open (image, books, book, review APIs). 

2. Users can register on the service by providing a username, password and a contact email  address (user_create). A login API allows to both admins and registered users to log in the  service and associate either admin or user authority with the session, so that access to the  other APIs can be controlled. Logout discards the current authority associated with the  session. 

3. Registered users can create reviews for a book (review_create), which contain a rating  (integer from 0 to 5). Users can modify or delete their own reviews, while admins can delete  any review (review_update, review_delete). 

4. When a user wants to purchase a book the service contacts Paypal to create a payment and  redirects the user to Paypal (purchase_create). Once the user approves the payment Paypal  redirects the user back to the activation API (purchase_activate). During activation the  service executes the payment on the Paypal service and if successful records the purchase of  the book. If the user cancels the payment she will be redirected to the purchase_cancel API.  The content of the book can be accessed at any time by users who purchased it  (book_download). 

5. The service will maintain a secure audit log about purchase requests, activations and  downloads. An admin user can get access to log entries using the log API. 
