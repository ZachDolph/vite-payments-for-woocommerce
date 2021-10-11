function createNewBlockSubByAddr(address, provider)
{
    const result = await provider.subscribe("createUnreceivedBlockSubscriptionByAddress", address);
    return result;
}


function getHashInfo(hash, provider)
{
    const result = await provider.request("ledger_getAccountBlockByHash", hash);
    return result;
}


function getTokenList(provider)
{
    const result = await provider.request('contract_getTokenInfoList', 0, 1000);
    return result;
}


function getTransactionHistory(address, provider, count)
{
    let httpRPC = new HTTP_RPC(httpURL);
    let provider = new ViteAPI(httpRPC, () =>
    {
        return;
    });
    let transactions = await provider.request("ledger_getBlocksByAccAddr", address, 0 , count);
    transactions = transactions.filter(tx => tx.fromAddress !== tx.toAddress && tx.blockType === 4);
    let statusTransaction = false;

    if (transactions !== null && transactions.length > 0)
    {
        for (var i = 0; i < transactions.length; i++)
        {
            if (await validateTransaction(transactions[i], tokenId, amount, provider))
            {
                // Success: Tx Confirmed
                statusTransaction = true;
                setState(1);
                break;
            }
        }
    }
    return statusTransaction;
}

async function validateTransaction(transaction, tokenId, amount, provider)
{
    let validated = false;
    let divider = `1e+${transaction.tokenInfo.decimals}`
    let amountTx = (new Big(`${transaction.amount}`)).div(Big(divider));

    if (transaction.fromBlockHash === "0000000000000000000000000000000000000000000000000000000000000000")
    {
        return false;
    }

    const request = await getHashInfo(transaction.fromBlockHash, provider);
    if(request !== null && parseInt(amount) === parseInt(amountTx) && tokenId === transaction.tokenId)
        validated = true;
  
    return validated;
  
}