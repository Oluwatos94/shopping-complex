# Accept-to-charge leads

Vendors are charged for a lead only when they **accept** it, not when a buyer taps a
contact link. The mode is switched with `LEAD_BILLING_MODE` (`click` = current behaviour,
`accept` = this flow), so the change can be merged dark and turned on once the WhatsApp
template below is approved.

## Why

In click mode the chargeable event is an HTTP GET that redirects to the vendor's `wa.me`
link. After the redirect the platform cannot tell whether a message was ever sent, and the
payer (the vendor) does not control the event. That leaves three holes:

- a buyer who opens WhatsApp and closes it still costs the vendor a lead;
- web buyers are identified by cookie or IP + User-Agent, so a rival can rotate identities
  and drain a vendor's coins between the velocity guards;
- the same honest buyer on a new IP or cleared cookies is billed again.

Accept mode moves the charge to an action the vendor performs, and identifies every buyer
by the WhatsApp number Meta delivered their message from.

## Flow

```
Buyer (bot or web button)
   │  bot: get_vendor_contact tool
   │  web: /contact/{slug} → wa.me/<PLATFORM_WHATSAPP_NUMBER>?text=… Ref: <token>
   ▼
LeadAcceptanceService::request()        → billable_leads.state = pending (0 coins)
   │  VendorLeadRequested
   ▼
SendLeadRequestToVendor                 → in-app notification + `lead_request` template
   │                                      with [Accept] [Decline] quick replies
   ▼
Vendor taps Accept (WhatsApp or /vendor/leads)
   ▼
LeadAcceptanceService::accept()         → debit coins, state = charged, accepted_at
   │  VendorLeadCharged
   ├─ vendor: reply with the buyer's wa.me link (WhatsApp tap) / shown on Leads page
   └─ buyer:  NotifyBuyerOfLeadOutcome sends the vendor's wa.me link

Decline → state = declined, buyer told, nothing charged
No answer within LEAD_ACCEPT_WINDOW_HOURS → ExpirePendingLeads → state = expired, buyer told
```

Rules worth knowing:

- A buyer already accepted by the vendor inside the 30-day window gets the vendor's contact
  again for free (`repeat_count` goes up, no new charge).
- A buyer declined by the vendor cannot re-request that vendor inside the window.
- A buyer can hold at most `LEAD_MAX_PENDING_PER_BUYER` open requests.
- If the vendor lacks coins when accepting, they are told how much to top up and the request
  stays open until it expires. The optional daily coin cap still applies.
- The buyer's number is never in the request template; the vendor's number is never sent to
  a buyer before acceptance. Public vendor and product pages now expose only `has_whatsapp`.

## WhatsApp template to create in Meta

Category **Utility**, name `lead_request` (or set `WHATSAPP_TEMPLATE_LEAD_REQUEST`),
language matching `WHATSAPP_TEMPLATE_LANGUAGE`.

Body, six parameters in this order:

```
Hi {{1}}, a buyer is looking for {{2}} near {{3}} and wants to contact you.
Cost to accept: {{4}} coins (your balance: {{5}}).
Accept within {{6}} hours to get their WhatsApp number.
```

Buttons: two **Quick reply** buttons, in this order:

1. `Accept`
2. `Decline`

The code sends the payloads `LEAD_ACCEPT:<lead id>` and `LEAD_DECLINE:<lead id>` for
button index 0 and 1; the webhook only honours them from the vendor's own number.

## Switching it on

1. Create and get approval for the `lead_request` template.
2. Set `PLATFORM_WHATSAPP_NUMBER` to Jiidaa's WhatsApp Business number (digits only).
3. Run the migration (`2026_09_29_000001_add_vendor_acceptance_to_billable_leads`).
4. Make sure the scheduler runs in production. `ExpirePendingLeads` runs every 15 minutes;
   without `schedule:work` requests never expire and buyers are never told. Accepting an
   overdue request is refused either way, so no one is charged late.
5. Set `LEAD_BILLING_MODE=accept`.

`LEAD_ACCEPT_WINDOW_HOURS` should stay under 24: the buyer is told the outcome with a
free-form message, which WhatsApp only delivers inside the 24-hour window opened by the
buyer's last message.

## Follow-ups not in this change

- Once accept mode is proven, delete the click path (`RecordBillableLead`, `bill()`,
  `CoinBurnGuard` velocity checks, `/c/{token}` click recording) and its tests.
- The bot's conversation history does not yet record the accept/decline/expire messages the
  buyer receives, so the AI does not know about them in the next turn.
- Web buyers now need WhatsApp to reach a vendor. That is the point, but worth watching in
  the funnel numbers.
