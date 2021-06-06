import { Badge } from 'react-bootstrap';
import React, { Component } from 'react';

class InstanceStateBadge extends Component {
    constructor(props) {
        super(props);

        this.state = {
            state: props.state
        };
    }

    render() {
        let badge;

        switch (this.props.state) {
            case 'stopped':
                badge = <Badge variant="default" {...this.props}>Stopped</Badge>;
                break;

            case 'starting':
                badge = <Badge variant="warning" {...this.props}>Starting</Badge>;
                break;

            case 'stopping':
                badge = <Badge variant="warning" {...this.props}>Stopping</Badge>
                break;

            case 'exporting':
                badge = <Badge variant="warning" {...this.props}>Exporting</Badge>
                break;

            case 'started':
                badge = <Badge variant="success" {...this.props}>Started</Badge>
                break;

            case 'error':
                badge = <Badge variant="danger" {...this.props}>Error</Badge>
                break;

            default:
                badge = <Badge variant="default" {...this.props}>{this.state.state}</Badge>
        }

        return badge;
    }
}

export default InstanceStateBadge;