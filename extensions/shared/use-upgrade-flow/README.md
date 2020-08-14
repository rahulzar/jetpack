UseUpgradeFlow hook
-------------------

Use this hook when you need to implement a component that leads the user to the checkout page.

### Usage

```es6
/**
 * Internal dependencies
 */
import useUpgradeFlow from '../../shared/use-upgrade-flow/index';

const myUPgradeComponent = () => {
	const [ checkoutUrl, goToCheckoutPage ] = useUpgradeFlow( onRedirect );
	return (
        <Button
            href={ checkoutUrl }
            onClick={ goToCheckoutPage }
        >
            CheckOut!
        </Button>
    );
};
```

### API

```es6
const [ checkoutUrl, goToCheckoutPage ] = useUpgradeFlow( onRedirect );
```

The hook returns an array with two items.


The first one (checkoutUrl) is a string with the checkout URL.
You can use this value to set the href of an anchor element, for instance.

The second item (goToCheckoutPage) is a function that can be used as a callback in an onClick event.
It redirects the user to the checkout URL, checking before whether the current post/page/etc has changes to save.
If so, it saves them before to redirect.

Optionally, the hook accepts a callback argument that will be run when the redirect process triggers. 
