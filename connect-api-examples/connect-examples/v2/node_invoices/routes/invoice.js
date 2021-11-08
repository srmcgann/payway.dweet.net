/*
Copyright 2020 Square Inc.

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

    http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.
*/

const express = require("express");
const {
  v4: uuidv4
} = require("uuid");
const {
  ordersApi,
  invoicesApi,
  customersApi,
  locationsApi
} = require("../util/square-client");

const router = express.Router();


/**
 * Matches: GET /invoice/view/:locationId/:customerId/:invoiceId
 *
 * Description:
 *  Renders the invoice detail view page that with buttons
 *  that can update the status of the invoice.
 *
 * Query Parameters:
 *  locationId: Id of the location that the invoice belongs to
 *  customerId: Id of the selected customer
 *  invoiceId: Id of the selected invoice
 */
router.get("/view/:locationId/:customerId/:invoiceId", async (req, res, next) => {
  const {
    locationId,
    customerId,
    invoiceId,
  } = req.params;
  try {
    // Get the invoice by invoice id
    const { result: { invoice } } = await invoicesApi.getInvoice(invoiceId);

    // Render the invoice detail view page
    res.render("invoice", {
      locationId,
      customerId,
      invoice,
      idempotencyKey: uuidv4(),
    });
  } catch (error) {
    next(error);
  }
});


/**
 * Matches: POST /invoice/create
 *
 * Description:
 *  Take the order item information and create an invoice.
 *  In this example, the invoice is created and scheduled to be sent
 *  at 10 minutes after the creation and payment due date is 7 days
 *  after the creation date.
 *
 *  The invoice is created to charge customer's card on file by default
 *  if there is an card on file. Otherwise, the invoice will be sent and
 *  paid by customer through the invoice's public url.
 *
 * Request Body:
 *  customerId: Id of the selected customer
 *  locationId: Id of the location that the invoice belongs to
 *  idempotencyKey: Unique identifier for request from client
 *  priceAmount: The amount of price for the order item
 *  name: The name of the order item
 */
router.post("/create", async (req, res, next) => {
  const {
    customerId,
    locationId,
    idempotencyKey,
    priceAmount,
    name,
  } = req.body;
  try {
    const { result : { customer } } = await customersApi.retrieveCustomer(customerId);
    const locationResponse = await locationsApi.retrieveLocation(locationId);
    const currency = locationResponse.result.location.currency;

    // Create an order to be attached to invoice
    const { result : { order } } = await ordersApi.createOrder({
      order: {
        locationId,
        customerId,
        lineItems: [{
          name,
          quantity: "1",
          basePriceMoney: {
            amount: parseInt(priceAmount),
            currency: currency,
          }
        }]
      },
      idempotencyKey, // Unique identifier for request
    });

    // We set two important time below, scheduledAt and dueDate.
    // scheduledAt is when the invoice will be delivered to the buyer
    // and dueDate is when the invoice will be charged.
    // If scheduledAt is before the due date, it will send an email with an explanation that
    // the card on file will be charged on the due date
    // if the scheduledAt is the same date as the due date (in the location timezone)
    // it will charge at the scheduledAt time and send a receipt after, instead of sending the upcoming charge notification.
    // scheduledAt should be never after dueDate.

    // Set the due date to 7 days from today
    const dueDate = new Date();
    dueDate.setDate(dueDate.getDate() + 7);
    const dueDateString = dueDate.toISOString().split("T")[0];
    // Set the scheduledAt to next 10 minutes
    const scheduledAt = new Date(Date.now() + 10 * 60 * 1000);
    const scheduledAtString = scheduledAt.toISOString();

    // Set the payment request based on the customer's card on file status
    let paymentRequest = null;
    if (customer.cards && customer.cards.length > 0) {
      // the current customer has a card on file
      // creating invoice with the payment request method CARD_ON_FILE
      // the invoice will be charged with the card on file on the due date
      paymentRequest = {
        requestType: "BALANCE",
        automaticPaymentSource: "CARD_ON_FILE",
        dueDate: dueDateString,
        cardId: customer.cards[0].id // Take the first card
      };
    } else {
      // the current customer doesn't have a card on file
      // creating invoice with the payment request method EMAIL and set a reminder
      // the invoice will be sent and paid by customer
      paymentRequest = {
        requestType: "BALANCE",
        automaticPaymentSource: "NONE",
        dueDate: dueDateString,
        reminders: [
          {
            message: "Your invoice is due tomorrow",
            relativeScheduledDays: -1
          }
        ]
      };
    }

    const requestBody = {
      idempotencyKey,
      invoice: {
        deliveryMethod: "EMAIL",
        orderId: order.id,
        locationId: locationId,
        title: name,
        scheduledAt: scheduledAtString,
        primaryRecipient: {
          customerId,
        },
        paymentRequests: [
          paymentRequest
        ],
        acceptedPaymentMethods: {
          bankAccount: true,
          squareGiftCard: true,
          card: false
        }
      }
    };

    console.log(requestBody);
    const { result : { invoice }} = await invoicesApi.createInvoice(requestBody);

    res.redirect(`view/${locationId}/${customerId}/${invoice.id}`);
  } catch (error) {
    if (error.errrors) {
      next(error.errrors);
    } else {
      next(error);
    }
  }
});


/**
 * Matches: POST /invoice/publish
 *
 * Description:
 *  Publish the invoice.
 *
 * Request Body:
 *  idempotencyKey: Unique identifier for request from client
 *  customerId: Id of the selected customer
 *  locationId: Id of the location that the invoice belongs to
 *  invoiceId: Id of the invoice
 *  invoiceVersion: The version of the invoice
 */
router.post("/publish", async (req, res, next) => {
  const {
    idempotencyKey,
    locationId,
    customerId,
    invoiceId,
    invoiceVersion,
  } = req.body;
  try {
    // publish invoice
    const { result } = await invoicesApi.publishInvoice(invoiceId, {
      version: parseInt(invoiceVersion),
      idempotencyKey,
    });

    // redirect to the invoice detail view page
    res.redirect(`view/${locationId}/${customerId}/${result.invoice.id}`);
  } catch (error) {
    next(error);
  }
});


/**
 * Matches: POST /invoice/cancel
 *
 * Description:
 *  Cancel the invoice.
 *
 * Request Body:
 *  customerId: Id of the selected customer
 *  locationId: Id of the location that the invoice belongs to
 *  invoiceId: Id of the invoice
 *  invoiceVersion: The version of the invoice
 */
router.post("/cancel", async (req, res, next) => {
  const {
    locationId,
    customerId,
    invoiceId,
    invoiceVersion,
  } = req.body;
  try {
    // cancel invoice
    await invoicesApi.cancelInvoice(invoiceId, {
      version: parseInt(invoiceVersion),
    });

    // redirect to invoice detail view page
    res.redirect(`view/${locationId}/${customerId}/${invoiceId}`);
  } catch (error) {
    next(error);
  }
});


/**
 * Matches: POST /invoice/delete
 *
 * Description:
 *  Delete the invoice.
 *
 * Request Body:
 *  locationId: Id of the location that the invoice belongs to
 *  customerId: Id of the selected customer
 *  invoiceId: Id of the invoice
 *  invoiceVersion: The version of the invoice
 */
router.post("/delete", async (req, res, next) => {
  const {
    locationId,
    customerId,
    invoiceId,
    invoiceVersion,
  } = req.body;
  try {
    // delete the invoice
    await invoicesApi.deleteInvoice(invoiceId, parseInt(invoiceVersion));

    // invoice doesn't exist anymore, return to the invoice management page after delete the invoice
    res.redirect(`/management/${locationId}/${customerId}`);
  } catch (error) {
    next(error);
  }
});

module.exports = router;
