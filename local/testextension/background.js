chrome.runtime.onInstalled.addListener(() => {
    console.log("Extension installed!");
});

chrome.tabs.onActivated.addListener((tab) => {
    console.log("Tab activated:", tab);
});
